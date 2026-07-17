<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateTeachers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'employee_id' => ['type' => 'VARCHAR', 'constraint' => 20],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'email' => ['type' => 'VARCHAR', 'constraint' => 150],
            'grade_level' => ['type' => 'VARCHAR', 'constraint' => 50],
            'submission_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0.00],
            'user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('employee_id');
        $this->forge->addUniqueKey('email');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'SET NULL');
        $this->forge->createTable('teachers');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'teacher_id' => ['type' => 'INT', 'unsigned' => true],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 100],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', '', 'CASCADE');
        $this->forge->createTable('teacher_subjects');
    }

    public function down()
    {
        $this->forge->dropTable('teacher_subjects', true);
        $this->forge->dropTable('teachers', true);
    }
}
