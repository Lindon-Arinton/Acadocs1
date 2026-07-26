<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * DepEd-style KPI report indicators (Gross/Net Enrolment Rate, Cohort
 * Survival, Repetition, Promotion, Retention, Graduation, Completion,
 * Transition, Drop Out rates, plus total/male/female enrollment) — one
 * row per school year, distinct from the dashboard-oriented `kpi_snapshots`
 * table. Populated either manually or via the Word (.docx) report import.
 */
class CreateKpiReportIndicators extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'school_year' => ['type' => 'VARCHAR', 'constraint' => 20],
            'gross_enrollment_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'net_enrollment_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'cohort_survival_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'repetition_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'promotion_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'retention_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'graduation_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'completion_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'transition_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'dropout_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'enrollment_total' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'enrollment_male' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'enrollment_female' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'imported_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('school_year');
        $this->forge->createTable('kpi_report_indicators');
    }

    public function down()
    {
        $this->forge->dropTable('kpi_report_indicators', true);
    }
}
