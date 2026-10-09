<?php

namespace App\Libraries;

use App\Controllers\Teacher\PerformanceMps;
use App\Models\AnnouncementModel;
use App\Models\AutomationLogModel;
use App\Models\MpsTestScoreModel;
use App\Models\NotificationModel;
use App\Models\TeacherModel;
use App\Models\UserModel;
use Config\Automation as AutomationConfig;
use Config\SchoolCalendar as CalendarConfig;

/**
 * Scheduled jobs. run() is safe to call as often as you like: each job only
 * acts when something is due, and one-time jobs are claimed in automation_log
 * so they never repeat.
 *
 *  - Scheduled announcements: publish (and notify everyone) when due; archive when expired.
 *  - Term reminders: when the End-of-Term Block starts, remind teachers to enter MPS;
 *    the day after the term ends, follow up with those still missing scores.
 *  - Report packs: the day after a term (and the school year) ends, tell the
 *    principal and ADAS the end-of-term / end-of-year pack is ready.
 *  - Cleanup (once a day): spent password-reset codes, old notifications,
 *    abandoned chats and their files, old import files, orphaned uploads.
 *
 * Triggered by `php spark acadocs:automate` and, as a fallback, page loads
 * (App\Filters\AutomationTick).
 */
class Automation
{
    private AutomationConfig $config;
    private AutomationLogModel $log;
    private string $today;

    /** @var list<string> what this run did, for the command output / log */
    private array $report = [];

    public function __construct(?string $today = null)
    {
        $this->config = config('Automation');
        $this->log    = new AutomationLogModel();
        $this->today  = $today ?? date('Y-m-d');
    }

    /** @return list<string> what was done (empty when nothing was due) */
    public function run(): array
    {
        $this->publishScheduledAnnouncements();
        $this->archiveExpiredAnnouncements();
        $this->termJobs();
        if ($this->log->claim('cleanup:' . $this->today)) {
            $this->cleanup();
        }

        if ($this->report !== []) {
            log_message('info', 'Automation: ' . implode(' | ', $this->report));
        }

        return $this->report;
    }

    // ── Announcements ────────────────────────────────────────────────

    private function publishScheduledAnnouncements(): void
    {
        $model = new AnnouncementModel();
        $due   = $model->where('notified', 0)->where('status', 'active')
            ->where('publish_at <=', date('Y-m-d H:i:s'))->findAll();

        foreach ($due as $a) {
            // Claim first so two overlapping runs can't both notify everyone.
            if ($this->log->claim('announcement-published:' . $a['id'])) {
                $sent           = $model->notifyEveryone($a);
                $this->report[] = 'Published "' . $a['title'] . '" (' . $sent . ' notified)';
            }
        }
    }

    private function archiveExpiredAnnouncements(): void
    {
        $model = new AnnouncementModel();
        $model->set('status', 'inactive')->where('status', 'active')
            ->where('expires_at IS NOT NULL', null, false)->where('expires_at <=', date('Y-m-d H:i:s'))->update();
        $archived = $model->db->affectedRows();
        if ($archived > 0) {
            $this->report[] = 'Archived ' . $archived . ' expired announcement(s)';
        }
    }

    // ── Term reminders and report packs ─────────────────────────────

    private function termJobs(): void
    {
        /** @var CalendarConfig $calendar */
        $calendar = config('SchoolCalendar');
        $window   = $this->config->termNoticeWindowDays;

        foreach ($calendar->years as $year => $info) {
            $lastTerm = max(array_keys($info['terms']));

            foreach ($info['terms'] as $term => $t) {
                $eotStart = null;
                foreach ($t['blocks'] as $block) {
                    if ($block['name'] === 'End-of-Term Block') {
                        $eotStart = $block['start'];
                    }
                }

                // End-of-Term Block has started (and the term isn't over yet).
                if ($eotStart !== null && $this->today >= $eotStart && $this->today <= $t['end']
                    && $this->log->claim("eot-start:{$year}:{$term}")) {
                    $this->remindTeachersEndOfTerm($year, $term, $t['end']);
                }

                // The term has ended (from the next day, within the notice window).
                $after = date('Y-m-d', strtotime($t['end'] . ' +1 day'));
                if ($this->today >= $after && $this->today <= date('Y-m-d', strtotime($after . " +{$window} days"))) {
                    if ($this->log->claim("eot-missing:{$year}:{$term}")) {
                        $this->followUpMissingScores($year, $term);
                    }
                    if ($this->log->claim("pack:{$year}:{$term}")) {
                        $this->announcePack($year, (string) $term);
                    }
                    if ($term === $lastTerm && $this->log->claim("pack:{$year}:year")) {
                        $this->announcePack($year, 'year');
                    }
                }
            }
        }
    }

    private function remindTeachersEndOfTerm(string $year, int $term, string $termEnd): void
    {
        $notes = new NotificationModel();
        $count = 0;
        foreach ((new UserModel())->active()->where('role', 'teacher')->findAll() as $teacher) {
            $notes->insert([
                'user_id'  => $teacher['id'],
                'type'     => 'term_reminder',
                'title'    => 'End of Term ' . $term . ': time to enter MPS scores',
                'sub'      => 'Enter all your Term ' . $term . ' scores by ' . date('M d, Y', strtotime($termEnd)),
                'url'      => base_url('performance/mps?year=' . urlencode($year) . '&term=' . $term),
                'ref_type' => 'term_reminder',
                'ref_id'   => $term,
                'is_read'  => 0,
            ]);
            $count++;
        }
        $this->report[] = "End-of-Term {$term} reminder sent to {$count} teacher(s)";
    }

    private function followUpMissingScores(string $year, int $term): void
    {
        $notes    = new NotificationModel();
        $teachers = new TeacherModel();
        $mpsPage  = new PerformanceMps();
        $scores   = (new MpsTestScoreModel())->forYearTerm($year, $term);
        $behind   = 0;

        foreach ((new UserModel())->active()->where('role', 'teacher')->findAll() as $user) {
            $teacher = $teachers->resolveForUser($user);
            if (! $teacher) {
                continue;
            }

            $rows       = PerformanceMps::performanceRows($mpsPage->handledCellsForTeacher((int) $teacher['id'], $year, $term), $scores);
            $incomplete = array_filter($rows, static fn (array $r) => $r['s1'] === null || $r['s2'] === null || $r['exam'] === null);
            if ($incomplete === []) {
                continue;
            }

            $behind++;
            $n = count($incomplete);
            $notes->insert([
                'user_id'  => $user['id'],
                'type'     => 'term_reminder',
                'title'    => 'Term ' . $term . ' MPS still missing for ' . $n . ' class' . ($n === 1 ? '' : 'es'),
                'sub'      => 'The term has ended. Please complete your scores.',
                'url'      => base_url('performance/mps?year=' . urlencode($year) . '&term=' . $term),
                'ref_type' => 'term_reminder',
                'ref_id'   => $term,
                'is_read'  => 0,
            ]);
        }

        // One summary for the principal(s).
        if ($behind > 0) {
            foreach ((new UserModel())->active()->where('role', 'admin')->findAll() as $admin) {
                $notes->insert([
                    'user_id'  => $admin['id'],
                    'type'     => 'term_reminder',
                    'title'    => $behind . ' teacher' . ($behind === 1 ? ' hasn\'t' : 's haven\'t') . ' finished Term ' . $term . ' MPS',
                    'sub'      => 'SY ' . str_replace('-', '–', $year) . ' · reminders sent',
                    'url'      => base_url('dashboard?year=' . urlencode($year)),
                    'ref_type' => 'term_reminder',
                    'ref_id'   => $term,
                    'is_read'  => 0,
                ]);
            }
        }
        $this->report[] = "Term {$term} missing-MPS follow-up: {$behind} teacher(s) reminded";
    }

    /** @param string $term '1'..'3', or 'year' for the end-of-year pack */
    private function announcePack(string $year, string $term): void
    {
        $label = $term === 'year' ? 'End-of-year report pack' : 'End-of-Term ' . $term . ' report pack';
        $notes = new NotificationModel();
        foreach ((new UserModel())->active()->whereIn('role', ['admin', 'adas'])->findAll() as $user) {
            $notes->insert([
                'user_id'  => $user['id'],
                'type'     => 'report_pack',
                'title'    => $label . ' is ready',
                'sub'      => 'SY ' . str_replace('-', '–', $year) . ' · enrollment, attendance, MPS, task compliance',
                'url'      => base_url('reports/term-pack?year=' . urlencode($year) . '&term=' . $term),
                'ref_type' => 'report_pack',
                'ref_id'   => $term === 'year' ? 0 : (int) $term,
                'is_read'  => 0,
            ]);
        }
        $this->report[] = $label . ' announced';
    }

    // ── Cleanup ─────────────────────────────────────────────────────

    private function cleanup(): void
    {
        $db = db_connect();

        // Spent or long-expired password-reset codes.
        $db->query('DELETE FROM password_resets WHERE used_at IS NOT NULL OR expires_at < NOW() - INTERVAL 1 DAY');
        $resets = $db->affectedRows();

        // Old notifications: read ones after a while, any after a year.
        $db->query('DELETE FROM notifications WHERE (is_read = 1 AND updated_at < NOW() - INTERVAL ? DAY) OR updated_at < NOW() - INTERVAL ? DAY',
            [$this->config->readNotificationDays, $this->config->anyNotificationDays]);
        $notifications = $db->affectedRows();

        [$chats, $chatFiles] = $this->cleanupChats();
        $imports  = $this->cleanupImportFiles();
        $orphans  = $this->quarantineOrphanedUploads();
        $purged   = $this->purgeQuarantine();

        $this->report[] = sprintf(
            'Cleanup: %d reset code(s), %d notification(s), %d abandoned chat(s), %d chat file(s), %d old import file(s), %d orphaned upload(s) quarantined, %d purged',
            $resets, $notifications, $chats, $chatFiles, $imports, $orphans, $purged
        );
    }

    /** @return array{0:int,1:int} [chats removed, chat files removed] */
    private function cleanupChats(): array
    {
        $db    = db_connect();
        $files = 0;

        // Chats nobody is in anymore (everyone deleted / left), and direct chats
        // that were opened but never written in (a name clicked, then left).
        $ids = array_column($db->query(
            "SELECT c.id FROM conversations c
             WHERE NOT EXISTS (SELECT 1 FROM conversation_participants p WHERE p.conversation_id = c.id)
                OR (c.type = 'direct' AND c.created_at < NOW() - INTERVAL 1 DAY
                    AND NOT EXISTS (SELECT 1 FROM messages m WHERE m.conversation_id = c.id))"
        )->getResultArray(), 'id');

        foreach ($ids as $id) {
            $files += $this->deleteTree(WRITEPATH . 'uploads/chat/' . (int) $id);
        }
        if ($ids !== []) {
            // Participants, messages and reactions go with it (ON DELETE CASCADE).
            $db->table('conversations')->whereIn('id', $ids)->delete();
        }

        // Unsent messages don't show their attachment anymore: drop the file.
        foreach ($db->query('SELECT id, attachment_path FROM messages WHERE deleted_at IS NOT NULL AND attachment_path IS NOT NULL')->getResultArray() as $m) {
            if (is_file($m['attachment_path']) && @unlink($m['attachment_path'])) {
                $files++;
            }
            $db->table('messages')->where('id', $m['id'])->update(['attachment_path' => null, 'attachment_name' => null, 'attachment_ext' => null]);
        }

        return [count($ids), $files];
    }

    /** Uploaded import sources (DTR, MPS, enrollment, KPI) are only needed while importing. */
    private function cleanupImportFiles(): int
    {
        $keep = array_flip(array_column(db_connect()->query('SELECT source_file FROM deped_kpi_reports WHERE source_file IS NOT NULL')->getResultArray(), 'source_file'));
        $cutoff  = time() - $this->config->importFileDays * 86400;
        $removed = 0;

        foreach (['time_records_imports', 'mps_imports', 'enrollment_imports', 'kpi_report_imports'] as $dir) {
            foreach (glob(WRITEPATH . 'uploads/' . $dir . '/*') ?: [] as $file) {
                if (is_file($file) && basename($file) !== 'index.html' && ! isset($keep[basename($file)])
                    && filemtime($file) < $cutoff && @unlink($file)) {
                    $removed++;
                }
            }
        }

        return $removed;
    }

    /**
     * Uploaded files no database row points to are moved (not deleted) to
     * writable/orphaned_uploads/<date>/, so a mistake can be undone; they're
     * deleted from there after $quarantineDays. Files under a day old are
     * skipped, in case their row is still being saved.
     */
    private function quarantineOrphanedUploads(): int
    {
        $db         = db_connect();
        $referenced = [];
        foreach ([
            ['documents', 'file_path'], ['document_files', 'file_path'], ['messages', 'attachment_path'],
            ['parent_meetings', 'attendance_file_path'], ['task_submissions', 'file_path'],
            ['task_submission_files', 'file_path'], ['templates', 'file_path'],
        ] as [$table, $column]) {
            foreach ($db->query("SELECT {$column} AS p FROM {$table} WHERE {$column} IS NOT NULL AND {$column} <> ''")->getResultArray() as $r) {
                $referenced[self::normalize($r['p'])] = true;
            }
        }
        foreach ($db->query("SELECT image AS f FROM announcements WHERE image IS NOT NULL AND image <> ''")->getResultArray() as $r) {
            $referenced[self::normalize(FCPATH . 'uploads/announcements/' . $r['f'])] = true;
        }
        foreach ($db->query("SELECT photo AS f FROM users WHERE photo IS NOT NULL AND photo <> ''")->getResultArray() as $r) {
            $referenced[self::normalize(FCPATH . 'uploads/avatars/' . $r['f'])] = true;
        }

        $roots = [
            WRITEPATH . 'uploads/chat', WRITEPATH . 'uploads/tasks', WRITEPATH . 'uploads/templates', WRITEPATH . 'uploads/documents',
            FCPATH . 'uploads/announcements', FCPATH . 'uploads/avatars',
        ];
        $quarantine = WRITEPATH . 'orphaned_uploads/' . $this->today . '/';
        $dayAgo     = time() - 86400;
        $moved      = 0;

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                $path = $file->getPathname();
                if (! $file->isFile() || $file->getFilename() === 'index.html' || $file->getMTime() > $dayAgo
                    || isset($referenced[self::normalize($path)])) {
                    continue;
                }
                $target = $quarantine . ltrim(str_replace([self::normalize(WRITEPATH), self::normalize(FCPATH)], ['writable/', 'public/'], self::normalize($path)), '/');
                if (! is_dir(dirname($target))) {
                    mkdir(dirname($target), 0755, true);
                }
                if (@rename($path, $target)) {
                    $moved++;
                }
            }
        }

        return $moved;
    }

    private function purgeQuarantine(): int
    {
        $cutoff = date('Y-m-d', strtotime($this->today . ' -' . $this->config->quarantineDays . ' days'));
        $purged = 0;
        foreach (glob(WRITEPATH . 'orphaned_uploads/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (basename($dir) < $cutoff) {
                $purged += $this->deleteTree($dir);
            }
        }

        return $purged;
    }

    /** Deletes a directory and everything in it; returns the number of files removed. */
    private function deleteTree(string $dir): int
    {
        if (! is_dir($dir)) {
            return 0;
        }
        $count = 0;
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } elseif (@unlink($item->getPathname())) {
                $count++;
            }
        }
        @rmdir($dir);

        return $count;
    }

    /** Stored paths mix "\" and "/" (Windows): compare them case- and slash-insensitively. */
    private static function normalize(string $path): string
    {
        return strtolower(str_replace('\\', '/', $path));
    }
}
