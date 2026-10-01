<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One-time cleanup: notifications whose task / submission / announcement
 * was deleted before deletes started removing them (NotificationModel::
 * deleteForTask() / deleteForRef()). Clicking those opened nothing.
 * Safe to re-run — it only ever removes rows pointing at missing records.
 */
class PruneOrphanedNotifications extends Migration
{
    public function up()
    {
        $n = $this->db->prefixTable('notifications');

        foreach ([
            ['task_assigned', 'tasks'],
            ['task_submission', 'tasks'],
            ['task_feedback', 'task_submissions'],
            ['announcement', 'announcements'],
        ] as [$refType, $table]) {
            $t = $this->db->prefixTable($table);
            $this->db->query(
                "DELETE n FROM {$n} n LEFT JOIN {$t} r ON r.id = n.ref_id WHERE n.ref_type = ? AND r.id IS NULL",
                [$refType]
            );
        }
    }

    public function down()
    {
        // Deleted notifications pointed at records that no longer exist — nothing to restore.
    }
}
