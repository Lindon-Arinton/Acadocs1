<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskSubmissionModel extends Model
{
    protected $table = 'task_submissions';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['task_id', 'user_id', 'file_path', 'file_name', 'notes', 'status', 'submitted_at'];

    public function forTask(int $taskId): array
    {
        return $this->select('task_submissions.*, users.name AS submitter_name')
            ->join('users', 'users.id = task_submissions.user_id')
            ->where('task_id', $taskId)
            ->orderBy('submitted_at', 'DESC')
            ->findAll();
    }

    public function findForTaskAndUser(int $taskId, int $userId): ?array
    {
        return $this->where('task_id', $taskId)->where('user_id', $userId)->first();
    }
}
