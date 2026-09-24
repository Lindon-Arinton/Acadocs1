<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSectionToMpsTestScores extends Migration
{
    private const OLD_KEY = 'mps_test_scores_school_year_term_grade_level_subject_test_period';
    private const NEW_KEY = 'mps_test_scores_year_term_grade_subject_section_period';

    public function up()
    {
        $this->forge->addColumn('mps_test_scores', [
            'section' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'subject'],
        ]);

        // A raw-score row with no section represents an already-blended value
        // (e.g. imported from the school's grade-level MPS workbook, which
        // doesn't break scores out by section). MySQL treats NULL as distinct
        // in a unique key, so duplicate blended imports still rely on the
        // app-level upsert in MpsCalculator rather than this constraint alone.
        $this->db->query('ALTER TABLE mps_test_scores DROP INDEX ' . self::OLD_KEY);
        $this->db->query('ALTER TABLE mps_test_scores ADD UNIQUE KEY ' . self::NEW_KEY . ' (school_year, term, grade_level, subject, section, test_period)');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE mps_test_scores DROP INDEX ' . self::NEW_KEY);
        $this->db->query('ALTER TABLE mps_test_scores ADD UNIQUE KEY ' . self::OLD_KEY . ' (school_year, term, grade_level, subject, test_period)');
        $this->forge->dropColumn('mps_test_scores', 'section');
    }
}
