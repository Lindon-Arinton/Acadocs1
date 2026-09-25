<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Admins now review task uploads from their Document Management folder.
 * An upload stays Pending until it is marked Reviewed or Returned (sent
 * back for revision); the old "Submitted" status becomes Pending.
 */
class AddPendingReturnedToTaskSubmissions extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('task_submissions', [
            'status' => ['type' => 'ENUM', 'constraint' => ['Submitted', 'Pending', 'Reviewed', 'Returned'], 'default' => 'Pending'],
        ]);
        $this->db->query("UPDATE task_submissions SET status = 'Pending' WHERE status = 'Submitted'");
        $this->forge->modifyColumn('task_submissions', [
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Reviewed', 'Returned'], 'default' => 'Pending'],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('task_submissions', [
            'status' => ['type' => 'ENUM', 'constraint' => ['Submitted', 'Pending', 'Reviewed', 'Returned'], 'default' => 'Submitted'],
        ]);
        $this->db->query("UPDATE task_submissions SET status = 'Submitted' WHERE status IN ('Pending', 'Returned')");
        $this->forge->modifyColumn('task_submissions', [
            'status' => ['type' => 'ENUM', 'constraint' => ['Submitted', 'Reviewed'], 'default' => 'Submitted'],
        ]);
    }
}
