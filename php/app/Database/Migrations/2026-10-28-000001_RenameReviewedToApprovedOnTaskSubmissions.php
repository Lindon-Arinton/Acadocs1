<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Task uploads are now Approved (not "Reviewed") or Returned; anything the
 * principal hasn't decided on stays Pending.
 */
class RenameReviewedToApprovedOnTaskSubmissions extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('task_submissions', [
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Reviewed', 'Approved', 'Returned'], 'default' => 'Pending'],
        ]);
        $this->db->query("UPDATE task_submissions SET status = 'Approved' WHERE status = 'Reviewed'");
        $this->forge->modifyColumn('task_submissions', [
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Approved', 'Returned'], 'default' => 'Pending'],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('task_submissions', [
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Reviewed', 'Approved', 'Returned'], 'default' => 'Pending'],
        ]);
        $this->db->query("UPDATE task_submissions SET status = 'Reviewed' WHERE status = 'Approved'");
        $this->forge->modifyColumn('task_submissions', [
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'Reviewed', 'Returned'], 'default' => 'Pending'],
        ]);
    }
}
