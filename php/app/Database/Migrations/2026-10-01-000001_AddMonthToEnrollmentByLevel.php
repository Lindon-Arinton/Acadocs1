<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Enrollment is reported as dated snapshots ("SY 2026-2027 Enrollment -
 * June 10"), so each upload is stored per month (YYYY-MM) and the dashboard
 * can filter by it. '' = no month (e.g. a DepEd KPI report's per-grade
 * table), used only when a year has no monthly snapshot. '' rather than NULL
 * so the unique key still prevents duplicates.
 */
class AddMonthToEnrollmentByLevel extends Migration
{
    public function up()
    {
        $this->forge->addColumn('enrollment_by_level', [
            'month' => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => false, 'default' => '', 'after' => 'school_year'],
        ]);
        $this->db->query('ALTER TABLE enrollment_by_level DROP INDEX enrollment_year_grade, ADD UNIQUE KEY enrollment_year_month_grade (school_year, month, grade_level)');
    }

    public function down()
    {
        $this->db->query("DELETE FROM enrollment_by_level WHERE month <> ''");
        $this->db->query('ALTER TABLE enrollment_by_level DROP INDEX enrollment_year_month_grade, ADD UNIQUE KEY enrollment_year_grade (school_year, grade_level)');
        $this->forge->dropColumn('enrollment_by_level', 'month');
    }
}
