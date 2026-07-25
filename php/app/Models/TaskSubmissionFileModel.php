<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskSubmissionFileModel extends Model
{
    protected $table = 'task_submission_files';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['task_submission_id', 'file_path', 'file_name'];

    public function forSubmission(int $submissionId): array
    {
        return $this->where('task_submission_id', $submissionId)->orderBy('id', 'ASC')->findAll();
    }

    public function forSubmissions(array $submissionIds): array
    {
        if ($submissionIds === []) {
            return [];
        }

        $grouped = [];
        foreach ($this->whereIn('task_submission_id', $submissionIds)->orderBy('id', 'ASC')->findAll() as $f) {
            $grouped[$f['task_submission_id']][] = $f;
        }

        return $grouped;
    }
}
