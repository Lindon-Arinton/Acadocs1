<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskAssigneeModel extends Model
{
    protected $table = 'task_assignees';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['task_id', 'user_id'];

    public function userIdsForTask(int $taskId): array
    {
        return array_column($this->where('task_id', $taskId)->findAll(), 'user_id');
    }

    public function taskIdsForUser(int $userId): array
    {
        return array_column($this->where('user_id', $userId)->findAll(), 'task_id');
    }

    /** Users assigned to a specific task, joined for name/role. */
    public function usersForTask(int $taskId): array
    {
        return $this->select('users.id, users.name, users.role')
            ->join('users', 'users.id = task_assignees.user_id')
            ->where('task_id', $taskId)
            ->orderBy('users.name', 'ASC')
            ->findAll();
    }
}
