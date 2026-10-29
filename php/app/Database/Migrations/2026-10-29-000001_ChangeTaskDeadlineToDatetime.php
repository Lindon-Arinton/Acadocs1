<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * tasks.deadline was a DATE (from an older SQL dump), so the time picked on
 * New Task (11:59 PM by default) was dropped and every task came due at
 * 12:00 AM — showing Overdue from the very start of its due day. Make it a
 * DATETIME; existing date-only deadlines become 11:59 PM on that day.
 */
class ChangeTaskDeadlineToDatetime extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('tasks', [
            'deadline' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->db->query("UPDATE tasks SET deadline = ADDTIME(deadline, '23:59:00') WHERE TIME(deadline) = '00:00:00'");
    }

    public function down()
    {
        $this->forge->modifyColumn('tasks', [
            'deadline' => ['type' => 'DATE', 'null' => false],
        ]);
    }
}
