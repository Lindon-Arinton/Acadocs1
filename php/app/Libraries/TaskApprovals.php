<?php

namespace App\Libraries;

use App\Models\NotificationModel;
use App\Models\TaskModel;
use App\Models\TaskSubmissionModel;
use App\Models\UserModel;

/**
 * "Approve All" for a task's uploads (Manage Documents and Tasks pages,
 * principal and ADAS): every Pending upload becomes Approved in one go.
 * Returned uploads stay as they are until the submitter resubmits, and the
 * reviewer's own upload is never included (ADAS can be assigned tasks too).
 */
class TaskApprovals
{
    /** $taskId null = across tasks (the caller narrows it down, e.g. whereIn + groupBy). */
    public function approvableQuery(?int $taskId, int $reviewerId): TaskSubmissionModel
    {
        $query = (new TaskSubmissionModel())
            ->where('status', 'Pending')
            ->where('user_id !=', $reviewerId);

        return $taskId === null ? $query : $query->where('task_id', $taskId);
    }

    public function approvableCount(int $taskId, int $reviewerId): int
    {
        return $this->approvableQuery($taskId, $reviewerId)->countAllResults();
    }

    /** Approves them all and notifies each submitter. Returns how many were approved. */
    public function approveAllPending(int $taskId, int $reviewerId): int
    {
        $task = (new TaskModel())->find($taskId);
        if (! $task) {
            return 0;
        }

        $submissions = $this->approvableQuery($taskId, $reviewerId)->findAll();
        if ($submissions === []) {
            return 0;
        }

        (new TaskSubmissionModel())->whereIn('id', array_column($submissions, 'id'))->set([
            'status'      => 'Approved',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ])->update();

        $roles         = array_column((new UserModel())->select('id, role')->whereIn('id', array_column($submissions, 'user_id'))->findAll(), 'role', 'id');
        $notifications = new NotificationModel();
        foreach ($submissions as $s) {
            $notifications->upsertGrouped(
                (int) $s['user_id'],
                'task_feedback',
                (int) $s['id'],
                'task_feedback',
                'Your upload was approved: ' . $task['title'],
                'Status: Approved',
                base_url(($roles[$s['user_id']] ?? null) === 'teacher' ? 'submit-documents' : 'my-tasks')
            );
        }

        return count($submissions);
    }
}
