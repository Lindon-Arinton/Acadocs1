<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateDepedKpiReports extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'school_year'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'gross_enrolment_rate'  => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'net_enrolment_rate'    => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'cohort_survival_rate'  => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'repetition_rate'       => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'promotion_rate'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'retention_rate'        => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'graduation_rate'       => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'completion_rate'       => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'transition_rate'       => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'dropout_rate'          => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'enrolment_total'       => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'enrolment_male'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'enrolment_female'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'source_file'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'            => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('school_year');
        $this->forge->createTable('deped_kpi_reports');
    }

    public function down()
    {
        $this->forge->dropTable('deped_kpi_reports', true);
    }
}
