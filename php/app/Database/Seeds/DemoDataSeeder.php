<?php

namespace App\Database\Seeds;

use App\Database\Seeds\Demo\AttendanceSeeder;
use App\Database\Seeds\Demo\CommunicationSeeder;
use App\Database\Seeds\Demo\OperationsSeeder;
use App\Database\Seeds\Demo\SchoolDataSeeder;
use App\Database\Seeds\Demo\TaskDataSeeder;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Seeder;

/**
 * Whole-system demo data on top of the real accounts, teachers and
 * templates: tasks and submissions, attendance, MPS/enrolment/KPIs, chat,
 * announcements, notifications, room inventory, links, parent meetings and
 * legacy documents â€” about 11,000 rows. Each module lives in Seeds/Demo/.
 *
 *   php spark db:seed DemoDataSeeder       (re-running replaces the last batch)
 *   php spark db:seed DemoDataPurgeSeeder  (removes it)
 *
 * Log in as any teacher (teacher123), the principal (admin123) or ADAS
 * (adas123) â€” see UserSeeder â€” to see it.
 *
 * Removal works from a manifest (writable/demo_seed.json) recording the id
 * range each table grew by and every file placed on disk. For tables that
 * real imports/entries update in place (time records, enrolment, KPI
 * reports, MPS and performance rows) it also stores a fingerprint per row,
 * and the purge keeps any row that no longer matches â€” so real data written
 * over a demo row after seeding is never deleted.
 */
class DemoDataSeeder extends Seeder
{
    /** Tag on tasks from the earlier tasks-only version of this seeder, removed on the next run. */
    private const LEGACY_TASK_MARKER = 'Demo Seeder';

    private const MANIFEST = WRITEPATH . 'demo_seed.json';

    private const MODULES = [
        AttendanceSeeder::class,
        SchoolDataSeeder::class,
        OperationsSeeder::class,
        TaskDataSeeder::class,
        CommunicationSeeder::class,
    ];

    /** Every table the modules write to, parents before children (purged in reverse). */
    private const TABLES = [
        'holidays', 'time_records',
        'enrollment_by_level', 'deped_kpi_reports', 'mps_test_scores', 'performance_by_subject', 'performance_by_level',
        'document_links', 'parent_meetings', 'room_properties', 'documents', 'document_feedback',
        'tasks', 'task_assignees', 'task_submissions', 'task_submission_files', 'task_feedback', 'document_folders',
        'announcements', 'conversations', 'conversation_participants', 'messages', 'message_reactions',
        'notifications',
    ];

    /** Tables whose rows real imports/entries may update in place â€” see the class comment. */
    private const FINGERPRINTED = ['time_records', 'enrollment_by_level', 'deped_kpi_reports', 'mps_test_scores', 'performance_by_subject', 'performance_by_level'];

    /** @var list<string> */
    private static array $files = [];

    public function run()
    {
        mt_srand(20260928);

        $removed = self::purge($this->db);
        if ($removed > 0) {
            CLI::write("Removed {$removed} rows from the previous demo batch.");
        }

        $before      = $this->maxIds();
        self::$files = [];

        $this->db->transBegin();

        try {
            foreach (self::MODULES as $module) {
                $this->call($module);
            }
            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            array_map(static fn ($f) => is_file($f) && unlink($f), self::$files);

            throw $e;
        }

        $manifest = ['seeded_at' => date('c'), 'ranges' => [], 'fingerprints' => [], 'files' => self::$files];
        $total    = 0;

        CLI::newLine();
        foreach (self::TABLES as $table) {
            $new = $this->db->table($table)->select('MIN(id) AS first, MAX(id) AS last, COUNT(*) AS n')
                ->where('id >', $before[$table])->get()->getRow();
            if ((int) $new->n === 0) {
                continue;
            }
            $manifest['ranges'][$table] = [(int) $new->first, (int) $new->last];

            if (in_array($table, self::FINGERPRINTED, true)) {
                foreach (self::rowsInRange($this->db, $table, $manifest['ranges'][$table]) as $row) {
                    $manifest['fingerprints'][$table][$row['id']] = self::fingerprint($row);
                }
            }

            $total += (int) $new->n;
            CLI::write(str_pad($table, 28) . $new->n);
        }
        CLI::write(str_pad('total rows', 28) . $total, 'green');
        CLI::write(str_pad('files placed', 28) . count(self::$files));

        file_put_contents(self::MANIFEST, json_encode($manifest));
    }

    /** Called by the modules for every file they put on disk. */
    public static function trackFile(string $path): void
    {
        self::$files[] = $path;
    }

    /**
     * Removes the last demo batch recorded in the manifest (plus any tasks
     * from the older tasks-only seeder). Returns the number of rows deleted,
     * not counting rows removed by FK cascades.
     */
    public static function purge(BaseConnection $db): int
    {
        $removed = self::purgeLegacyTasks($db);

        if (! is_file(self::MANIFEST)) {
            return $removed;
        }
        $manifest = json_decode((string) file_get_contents(self::MANIFEST), true);

        foreach (array_reverse(self::TABLES) as $table) {
            $range = $manifest['ranges'][$table] ?? null;
            if ($range === null) {
                continue;
            }

            if (isset($manifest['fingerprints'][$table])) {
                $prints = $manifest['fingerprints'][$table];
                $ids    = [];
                foreach (self::rowsInRange($db, $table, $range) as $row) {
                    if (($prints[$row['id']] ?? null) === self::fingerprint($row)) {
                        $ids[] = $row['id'];
                    }
                }
                foreach (array_chunk($ids, 500) as $chunk) {
                    $db->table($table)->whereIn('id', $chunk)->delete();
                    $removed += $db->affectedRows();
                }
            } else {
                $db->table($table)->where('id >=', $range[0])->where('id <=', $range[1])->delete();
                $removed += $db->affectedRows();
            }
        }

        $dirs = [];
        foreach ($manifest['files'] ?? [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
            $dirs[dirname($file)] = true;
        }
        foreach (array_keys($dirs) as $dir) {
            if (is_dir($dir) && glob($dir . '/*') === []) {
                rmdir($dir);
            }
        }

        unlink(self::MANIFEST);

        return $removed;
    }

    private static function purgeLegacyTasks(BaseConnection $db): int
    {
        $taskIds = array_column($db->table('tasks')->select('id')->where('created_by', self::LEGACY_TASK_MARKER)->get()->getResultArray(), 'id');
        if ($taskIds === []) {
            return 0;
        }

        foreach ($taskIds as $taskId) {
            $dir = WRITEPATH . 'uploads/tasks/' . $taskId;
            foreach (glob($dir . '/*') ?: [] as $file) {
                unlink($file);
            }
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
        $db->table('tasks')->whereIn('id', $taskIds)->delete();

        return $db->affectedRows();
    }

    /** @return array<string,int> table => current MAX(id) */
    private function maxIds(): array
    {
        $ids = [];
        foreach (self::TABLES as $table) {
            $ids[$table] = (int) ($this->db->table($table)->selectMax('id')->get()->getRow()->id ?? 0);
        }

        return $ids;
    }

    private static function rowsInRange(BaseConnection $db, string $table, array $range): array
    {
        return $db->table($table)->where('id >=', $range[0])->where('id <=', $range[1])->get()->getResultArray();
    }

    private static function fingerprint(array $row): string
    {
        return md5(json_encode($row));
    }
}
