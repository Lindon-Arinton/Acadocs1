<?php

namespace App\Models;

use CodeIgniter\Model;

class TaskFeedbackModel extends Model
{
    protected $table = 'task_feedback';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['task_submission_id', 'comment', 'author_id', 'date'];
    /**
     * Feedback on one submission, newest first, with the author's name
     * (author_name is NULL for comments written before authors were recorded).
     */
    public function forSubmission(int $submissionId): array
    {
        return $this->withAuthor()->where('task_feedback.task_submission_id', $submissionId)
            ->orderBy('task_feedback.id', 'DESC')->findAll();
    }

    /** @return array<int,array<int,array<string,mixed>>> submission id => feedback rows (newest first) */
    public function forSubmissions(array $submissionIds): array
    {
        if ($submissionIds === []) {
            return [];
        }

        $grouped = [];
        foreach ($this->withAuthor()->whereIn('task_feedback.task_submission_id', $submissionIds)->orderBy('task_feedback.id', 'DESC')->findAll() as $row) {
            $grouped[$row['task_submission_id']][] = $row;
        }

        return $grouped;
    }

    private function withAuthor(): self
    {
        return $this->select('task_feedback.*, users.name AS author_name')
            ->join('users', 'users.id = task_feedback.author_id', 'left');
    }
}
