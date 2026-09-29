<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentFolderModel extends Model
{
    protected $table = 'document_folders';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['task_id', 'name'];

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
     * Folders with their submitter/file counts and latest upload time.
     */
    public function withStats(string $search = ''): array
    {
        $builder = $this->db->table('document_folders f')
            ->select("f.*, COUNT(DISTINCT s.id) AS submitter_count, COUNT(tsf.id) AS file_count, MAX(s.submitted_at) AS last_upload,
                      COUNT(DISTINCT CASE WHEN s.status = 'Pending' THEN s.id END) AS to_review_count", false)
            ->join('task_submissions s', 's.task_id = f.task_id', 'left')
            ->join('task_submission_files tsf', 'tsf.task_submission_id = s.id', 'left')
            ->groupBy('f.id')
            ->orderBy('last_upload', 'DESC');

        if ($search !== '') {
            $builder->like('f.name', $search);
        }

        return $builder->get()->getResultArray();
    }
}
