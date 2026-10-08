<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Document Management folders, organized like a file explorer:
 *  - a task folder (task_id set) holds the uploads for one task and is
 *    auto-created on the first upload;
 *  - a plain folder (task_id NULL, e.g. "School Forms") only holds other
 *    folders.
 * Any folder can sit inside a plain folder via parent_id (NULL = top level).
 */
class DocumentFolderModel extends Model
{
    protected $table = 'document_folders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['task_id', 'parent_id', 'name'];

    /**
     * Folder name: task title + the date the task was created,
     * e.g. "Submit Q1 DLL - Jul 25, 2026".
     */
    public static function nameForTask(array $task): string
    {
        return $task['title'] . ' - ' . date('M d, Y', strtotime($task['created_at']));
    }

    /**
     * Returns the task's folder id, creating the folder on first call.
     */
    public function ensureForTask(array $task): int
    {
        $existing = $this->where('task_id', $task['id'])->first();

        if ($existing) {
            return (int) $existing['id'];
        }

        return (int) $this->insert([
            'task_id' => $task['id'],
            'name'    => self::nameForTask($task),
        ]);
    }

    /**
     * Every folder with its upload stats, keyed by id. Plain folders also get
     * totals rolled up from everything inside them (files, pending reviews,
     * latest upload), so a "School Forms" card can show "3 pending".
     *
     * @return array<int,array<string,mixed>>
     */
    public function tree(): array
    {
        $rows = $this->db->table('document_folders f')
            ->select("f.*, COUNT(DISTINCT s.id) AS submitter_count, COUNT(tsf.id) AS file_count, MAX(s.submitted_at) AS last_upload,
                      COUNT(DISTINCT CASE WHEN s.status = 'Pending' THEN s.id END) AS to_review_count", false)
            ->join('task_submissions s', 's.task_id = f.task_id', 'left')
            ->join('task_submission_files tsf', 'tsf.task_submission_id = s.id', 'left')
            ->groupBy('f.id')
            ->get()->getResultArray();

        $folders = [];
        foreach ($rows as $row) {
            $row['id']              = (int) $row['id'];
            $row['parent_id']       = $row['parent_id'] !== null ? (int) $row['parent_id'] : null;
            $row['is_task']         = $row['task_id'] !== null;
            $row['file_count']      = (int) $row['file_count'];
            $row['submitter_count'] = (int) $row['submitter_count'];
            $row['to_review_count'] = (int) $row['to_review_count'];
            $row['child_count']     = 0;
            $folders[$row['id']]    = $row;
        }

        // Roll each folder's own stats up into every ancestor.
        foreach ($folders as $id => $folder) {
            if ($folder['parent_id'] !== null && isset($folders[$folder['parent_id']])) {
                $folders[$folder['parent_id']]['child_count']++;
            }
            if (! $folder['is_task']) {
                continue;
            }
            $seen     = [];
            $parentId = $folder['parent_id'];
            while ($parentId !== null && isset($folders[$parentId]) && ! isset($seen[$parentId])) {
                $seen[$parentId] = true;
                $folders[$parentId]['file_count']      += $folder['file_count'];
                $folders[$parentId]['to_review_count'] += $folder['to_review_count'];
                if ($folder['last_upload'] && $folder['last_upload'] > (string) $folders[$parentId]['last_upload']) {
                    $folders[$parentId]['last_upload'] = $folder['last_upload'];
                }
                $parentId = $folders[$parentId]['parent_id'];
            }
        }

        return $folders;
    }

    /**
     * Folders directly inside $parentId (NULL = top level): plain folders
     * first, then task folders by most recent upload.
     *
     * @param array<int,array<string,mixed>> $tree from tree()
     */
    public static function childrenOf(array $tree, ?int $parentId): array
    {
        $children = array_values(array_filter($tree, static fn ($f) => $f['parent_id'] === $parentId));

        usort($children, static function ($a, $b) {
            if ($a['is_task'] !== $b['is_task']) {
                return $a['is_task'] ? 1 : -1;
            }
            if (! $a['is_task']) {
                return strcasecmp($a['name'], $b['name']);
            }

            return strcmp((string) $b['last_upload'], (string) $a['last_upload']) ?: strcasecmp($a['name'], $b['name']);
        });

        return $children;
    }

    /**
     * Breadcrumb from the top level down to (and including) $folderId.
     *
     * @param array<int,array<string,mixed>> $tree
     * @return list<array<string,mixed>>
     */
    public static function pathTo(array $tree, int $folderId): array
    {
        $path = [];
        $seen = [];
        for ($id = $folderId; $id !== null && isset($tree[$id]) && ! isset($seen[$id]); $id = $tree[$id]['parent_id']) {
            $seen[$id] = true;
            array_unshift($path, $tree[$id]);
        }

        return $path;
    }

    /**
     * Ids of every folder nested anywhere inside $folderId.
     *
     * @param array<int,array<string,mixed>> $tree
     * @return list<int>
     */
    public static function descendantIds(array $tree, int $folderId): array
    {
        $ids   = [];
        $queue = [$folderId];
        while ($queue) {
            $current = array_shift($queue);
            foreach ($tree as $f) {
                if ($f['parent_id'] === $current && ! in_array($f['id'], $ids, true)) {
                    $ids[]   = $f['id'];
                    $queue[] = $f['id'];
                }
            }
        }

        return $ids;
    }
}
