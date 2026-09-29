<?php

namespace App\Database\Seeds\Demo;

use App\Database\Seeds\DemoDataSeeder;
use CodeIgniter\Database\Seeder;

/**
 * Shared lookups and helpers for the per-module demo seeders in this folder.
 * They're run (in order, inside one transaction) by DemoDataSeeder, which
 * also records what they inserted so it can be removed again — don't run
 * them on their own.
 */
abstract class DemoSeeder extends Seeder
{
    // Same list Teacher\SubmitDocuments accepts for uploads.
    protected const UPLOAD_EXT = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];

    /** @var list<array<string,mixed>> */
    private array $pendingNotifications = [];

    /**
     * Teacher accounts (users.role = teacher) with their teachers-row id,
     * advisory and base subject load ([subject, grade_level, section] rows).
     *
     * @return list<array<string,mixed>>
     */
    protected function teachers(): array
    {
        $rows = $this->db->table('users u')
            ->select('u.id, u.name, u.role, u.ac_no, t.id AS teacher_id, t.advisory')
            ->join('teachers t', 't.email = u.email OR t.user_id = u.id', 'left', false)
            ->where('u.role', 'teacher')
            ->orderBy('u.id')->orderBy('t.id')
            ->get()->getResultArray();

        $subjects = [];
        foreach ($this->db->table('teacher_subjects')->where('school_year', null)->get()->getResultArray() as $s) {
            $subjects[$s['teacher_id']][] = $s;
        }

        // One entry per user even if both join conditions matched different rows.
        $teachers = [];
        foreach ($rows as $row) {
            $row['subjects']        = $subjects[$row['teacher_id']] ?? [];
            $teachers[$row['id']] ??= $row;
        }

        return array_values($teachers);
    }

    /** Every user account (any role). */
    protected function users(): array
    {
        return $this->db->table('users')->select('id, name, role, ac_no')->orderBy('id')->get()->getResultArray();
    }

    /** The principal: the admin whose position says so, else the first admin. */
    protected function principal(): array
    {
        $admins = $this->db->table('users')->where('role', 'admin')->orderBy('id')->get()->getResultArray();
        foreach ($admins as $admin) {
            if (stripos((string) $admin['position'], 'principal') !== false) {
                return $admin;
            }
        }

        return $admins[0] ?? throw new \RuntimeException('No admin account found — run UserSeeder first.');
    }

    /** The first ADAS account, or the principal when there is none. */
    protected function adas(): array
    {
        return $this->db->table('users')->where('role', 'adas')->where('ac_no IS NOT NULL')->orderBy('id')->get()->getRowArray()
            ?? $this->principal();
    }

    /**
     * Every section the school has, grouped by grade — from teacher_subjects,
     * the same source the Property Management page uses.
     *
     * @return array<string,list<string>>
     */
    protected function sectionsByGrade(): array
    {
        $map = [];
        $rows = $this->db->table('teacher_subjects')->distinct()->select('grade_level, section')
            ->where('section IS NOT NULL')->where('section !=', '')
            ->orderBy('grade_level')->orderBy('section')
            ->get()->getResultArray();

        foreach ($rows as $row) {
            $map[$row['grade_level']][] = $row['section'];
        }
        uksort($map, static fn ($a, $b) => (int) filter_var($a, FILTER_SANITIZE_NUMBER_INT) <=> (int) filter_var($b, FILTER_SANITIZE_NUMBER_INT));

        return $map;
    }

    /**
     * Uploaded templates whose file actually exists on this machine. Paths
     * are stored absolute, so a DB imported from another machine points
     * elsewhere — fall back to the same file name under this install's
     * uploads/templates/{category_id}/ folder.
     *
     * @return list<array{title:string,path:string,file_name:string,ext:string}>
     */
    protected function templateFiles(): array
    {
        $files = [];

        foreach ($this->db->table('templates')->orderBy('id')->get()->getResultArray() as $t) {
            $ext = strtolower($t['file_ext'] ?: pathinfo($t['file_name'], PATHINFO_EXTENSION));
            if (! in_array($ext, self::UPLOAD_EXT, true)) {
                continue;
            }

            $path = $t['file_path'];
            if (! is_file($path)) {
                $path = WRITEPATH . 'uploads/templates/' . $t['category_id'] . '/' . basename(str_replace('\\', '/', $t['file_path']));
            }
            if (! is_file($path)) {
                continue;
            }

            $files[] = ['title' => $t['title'], 'path' => $path, 'file_name' => $t['file_name'], 'ext' => $ext];
        }

        if ($files === []) {
            throw new \RuntimeException('No template files found on disk — upload some on the Templates page first.');
        }

        return $files;
    }

    /**
     * Puts a template file into $dir under a fresh random name (like an
     * uploaded file gets) and returns its path. A hard link where the
     * filesystem allows it (no extra disk space), a copy otherwise — never
     * the template path itself, since replacing an upload unlinks the old
     * file and would take the template with it.
     */
    protected function placeFile(array $file, string $dir, int $ts): string
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $target = $dir . DIRECTORY_SEPARATOR . $ts . '_' . bin2hex(random_bytes(10)) . '.' . $file['ext'];
        if (! @link($file['path'], $target) && ! copy($file['path'], $target)) {
            throw new \RuntimeException('Could not create ' . $target);
        }
        DemoDataSeeder::trackFile($target);

        return $target;
    }

    /**
     * Queues a notification shaped like the app's own (see e.g.
     * Admin\Tasks, Shared\Announcements). Older ones are mostly read, so
     * the bell shows a believable handful of unread items rather than
     * hundreds. Written by flushNotifications().
     */
    protected function notify(int $userId, string $type, string $title, ?string $sub, string $url, ?string $refType, ?int $refId, int $ts): void
    {
        $isOld = time() - $ts > 7 * 86400;

        $this->pendingNotifications[] = [
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => mb_substr($title, 0, 255),
            'sub'        => $sub === null ? null : mb_substr($sub, 0, 255),
            'url'        => $url,
            'ref_type'   => $refType,
            'ref_id'     => $refId,
            'is_read'    => $this->chance($isOld ? 95 : 35) ? 1 : 0,
            'created_at' => $this->at($ts),
            'updated_at' => $this->at($ts),
        ];
    }

    protected function flushNotifications(): int
    {
        $count = count($this->pendingNotifications);
        if ($count > 0) {
            $this->db->table('notifications')->insertBatch($this->pendingNotifications);
            $this->pendingNotifications = [];
        }

        return $count;
    }

    protected function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    protected function pick(array $items): mixed
    {
        return $items[array_rand($items)];
    }

    protected function at(int $ts): string
    {
        return date('Y-m-d H:i:s', $ts);
    }

    /** Start of the day $days from today (negative = past). */
    protected function day(int $days): int
    {
        return strtotime('today') + $days * 86400;
    }
}
