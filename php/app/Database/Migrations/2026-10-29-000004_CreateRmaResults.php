<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Rapid Mathematics Assessment (RMA), Grades 7–10: one row per section and
 * sex for a school year + assessment period, entered by the section's Math
 * teacher (Teacher\Rma) and shown school-wide on the principal's dashboard.
 */
class CreateRmaResults extends Migration
{
    public function up()
    {
        $count = static fn () => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true];
        $score = static fn () => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true];

        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'school_year'           => ['type' => 'VARCHAR', 'constraint' => 9],
            'period'                => ['type' => 'ENUM', 'constraint' => ['BoSY', 'MoSY', 'EoSY']],
            'grade_level'           => ['type' => 'VARCHAR', 'constraint' => 20],
            'section'               => ['type' => 'VARCHAR', 'constraint' => 100],
            'sex'                   => ['type' => 'ENUM', 'constraint' => ['Male', 'Female']],
            'enrolment'             => $count(),
            'assessed'              => $count(),
            'lvl_not_proficient'    => $count(),
            'lvl_low_proficient'    => $count(),
            'lvl_nearly_proficient' => $count(),
            'lvl_proficient'        => $count(),
            'lvl_highly_proficient' => $count(),
            'cd_number_algebra'     => $score(),
            'cd_measurement_geometry' => $score(),
            'cd_data_probability'   => $score(),
            'cg_knowing'            => $score(),
            'cg_interpreting'       => $score(),
            'cg_applying'           => $score(),
            'cg_reasoning'          => $score(),
            'mean_score'            => $score(),
            'entered_by'            => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'            => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['school_year', 'period', 'grade_level', 'section', 'sex'], 'rma_results_unique_row');
        $this->forge->addKey('entered_by');
        $this->forge->addForeignKey('entered_by', 'users', 'id', '', 'SET NULL', 'rma_results_entered_by_fk');
        $this->forge->createTable('rma_results', false, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('rma_results', true);
    }
}
