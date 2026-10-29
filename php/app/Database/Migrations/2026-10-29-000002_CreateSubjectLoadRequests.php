<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Teachers no longer change their subject load directly: each add, change or
 * remove of a subject is a request form (with a reason) that the principal or
 * ADAS approves or rejects. `proposed` holds the change as JSON — see
 * SubjectLoadRequestModel.
 */
class CreateSubjectLoadRequests extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'teacher_id'  => ['type' => 'INT', 'unsigned' => true],
            'user_id'     => ['type' => 'INT', 'unsigned' => true],
            'school_year' => ['type' => 'VARCHAR', 'constraint' => 9],
            'term'        => ['type' => 'TINYINT', 'unsigned' => true],
            'proposed'    => ['type' => 'TEXT'],
            'reason'      => ['type' => 'TEXT', 'null' => true],
            'status'      => ['type' => 'ENUM', 'constraint' => ['Pending', 'Approved', 'Rejected'], 'default' => 'Pending'],
            'reviewed_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reviewed_at' => ['type' => 'DATETIME', 'null' => true],
            'review_note' => ['type' => 'TEXT', 'null' => true],
            'created_at'  => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->addKey(['teacher_id', 'school_year', 'term']);
        $this->forge->addKey('user_id');
        $this->forge->addKey('reviewed_by');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', '', 'CASCADE', 'subject_load_requests_teacher_fk');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE', 'subject_load_requests_user_fk');
        $this->forge->addForeignKey('reviewed_by', 'users', 'id', '', 'SET NULL', 'subject_load_requests_reviewer_fk');
        $this->forge->createTable('subject_load_requests');
    }

    public function down()
    {
        $this->forge->dropTable('subject_load_requests', true);
    }
}
