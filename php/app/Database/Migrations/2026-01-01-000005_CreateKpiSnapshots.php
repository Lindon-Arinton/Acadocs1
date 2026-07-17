<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateKpiSnapshots extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'school_year' => ['type' => 'VARCHAR', 'constraint' => 20],
            'total_enrollment' => ['type' => 'INT', 'unsigned' => true],
            'submission_compliance' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'average_mps' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'dropout_count' => ['type' => 'INT', 'unsigned' => true],
            'parent_attendance' => ['type' => 'DECIMAL', 'constraint' => '5,2'],
            'recorded_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('kpi_snapshots');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'school_year' => ['type' => 'VARCHAR', 'constraint' => 20],
            'grade_level' => ['type' => 'VARCHAR', 'constraint' => 50],
            'students' => ['type' => 'INT', 'unsigned' => true],
            'sections' => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('enrollment_by_level');
    }

    public function down()
    {
        $this->forge->dropTable('enrollment_by_level', true);
        $this->forge->dropTable('kpi_snapshots', true);
    }
}
