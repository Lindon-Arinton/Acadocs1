<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['user_id', 'type', 'title', 'sub', 'url', 'ref_type', 'ref_id', 'is_read'];

    public function forUser(int $userId, int $limit = 8): array
    {
        return $this->where('user_id', $userId)->orderBy('updated_at', 'DESC')->findAll($limit);
    }

    public function unreadCount(int $userId): int
    {
        return $this->where('user_id', $userId)->where('is_read', 0)->countAllResults();
    }

    public function markRead(int $id, int $userId): void
    {
        $this->set(['is_read' => 1])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->update();
    }

    /**
     * Insert or refresh a notification grouped by (user, ref_type, ref_id) — used for
     * events like task submissions where repeated occurrences should update a single
     * row ("Maria and 2 others submitted") instead of piling up duplicates.
     */
    public function upsertGrouped(int $userId, string $refType, int $refId, string $type, string $title, ?string $sub, ?string $url): void
    {
        $existing = $this->where('user_id', $userId)
            ->where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->first();

        if ($existing) {
            $this->update($existing['id'], [
                'title'   => $title,
                'sub'     => $sub,
                'url'     => $url,
                'is_read' => 0,
            ]);

            return;
        }

        $this->insert([
            'user_id'  => $userId,
            'type'     => $type,
            'title'    => $title,
            'sub'      => $sub,
            'url'      => $url,
            'ref_type' => $refType,
            'ref_id'   => $refId,
            'is_read'  => 0,
        ]);
    }
    /**
     * Removes every notification about the given record(s) — call this when
     * the record itself is deleted, so nobody is left with a notification
     * that opens nothing.
     *
     * @param int|int[] $refIds
     */
    public function deleteForRef(string $refType, int|array $refIds): void
    {
        $refIds = array_values(array_filter(array_map('intval', (array) $refIds)));
        if ($refIds !== []) {
            $this->where('ref_type', $refType)->whereIn('ref_id', $refIds)->delete();
        }
    }

    /**
     * Notifications about a task: its assignment/submission notices plus the
     * feedback notices on its submissions. Call before deleting the task
     * (the submissions cascade away with it, taking their ids along).
     */
    public function deleteForTask(int $taskId): void
    {
        $this->deleteForRef('task_assigned', $taskId);
        $this->deleteForRef('task_submission', $taskId);
        $this->deleteForRef('task_feedback', array_column(
            $this->db->table('task_submissions')->select('id')->where('task_id', $taskId)->get()->getResultArray(),
            'id'
        ));
    }
}
