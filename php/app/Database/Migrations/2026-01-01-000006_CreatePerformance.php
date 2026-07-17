<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePerformance extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'school_year' => ['type' => 'VARCHAR', 'constraint' => 20],
            'grade_level' => ['type' => 'VARCHAR', 'constraint' => 50],
            'mps' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'nds' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('performance_by_level');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'school_year' => ['type' => 'VARCHAR', 'constraint' => 20],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 100],
            'grade_level' => ['type' => 'VARCHAR', 'constraint' => 50],
            'instructor' => ['type' => 'VARCHAR', 'constraint' => 100],
            'mps' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('performance_by_subject');
    }

    public function down()
    {
        $this->forge->dropTable('performance_by_subject', true);
        $this->forge->dropTable('performance_by_level', true);
    }
}
