<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Reading Program (Phil-IRI Filipino / English, GST, Project TALA):
 *  - reading_assignments: who the principal assigned to encode each language;
 *  - reading_results: one row per school year + period + language + section,
 *    with enrolled / tested learners and the level counts as JSON (see
 *    ReadingResultModel), Draft → Submitted → Approved / Returned.
 * Filipino is assigned to Remelyn (Labajo) Diaz to start.
 */
class CreateReadingProgram extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'language'    => ['type' => 'ENUM', 'constraint' => ['Filipino', 'English']],
            'user_id'     => ['type' => 'INT', 'unsigned' => true],
            'assigned_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['language', 'user_id'], 'reading_assignments_unique');
        $this->forge->addKey('user_id');
        $this->forge->addKey('assigned_by');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE', 'reading_assignments_user_fk');
        $this->forge->addForeignKey('assigned_by', 'users', 'id', '', 'SET NULL', 'reading_assignments_by_fk');
        $this->forge->createTable('reading_assignments', false, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $count = static fn () => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true];
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'school_year'  => ['type' => 'VARCHAR', 'constraint' => 9],
            'period'       => ['type' => 'ENUM', 'constraint' => ['Beginning', 'End']],
            'language'     => ['type' => 'ENUM', 'constraint' => ['Filipino', 'English']],
            'grade_level'  => ['type' => 'VARCHAR', 'constraint' => 20],
            'section'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'enrolled_m'   => $count(),
            'enrolled_f'   => $count(),
            'tested_m'     => $count(),
            'tested_f'     => $count(),
            'counts'       => ['type' => 'TEXT', 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['Draft', 'Submitted', 'Approved', 'Returned'], 'default' => 'Draft'],
            'review_note'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'updated_by'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'submitted_at' => ['type' => 'DATETIME', 'null' => true],
            'reviewed_by'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'reviewed_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['school_year', 'period', 'language', 'grade_level', 'section'], 'reading_results_unique');
        $this->forge->addKey('updated_by');
        $this->forge->addKey('reviewed_by');
        $this->forge->addForeignKey('updated_by', 'users', 'id', '', 'SET NULL', 'reading_results_updated_by_fk');
        $this->forge->addForeignKey('reviewed_by', 'users', 'id', '', 'SET NULL', 'reading_results_reviewed_by_fk');
        $this->forge->createTable('reading_results', false, ['ENGINE' => 'InnoDB', 'DEFAULT CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_unicode_ci']);

        // Filipino to Remelyn (Labajo) Diaz, as the principal asked.
        $this->db->query("INSERT IGNORE INTO reading_assignments (language, user_id) SELECT 'Filipino', id FROM users WHERE email = 'remelyn.labajo@deped.gov.ph'");
    }

    public function down()
    {
        $this->forge->dropTable('reading_results', true);
        $this->forge->dropTable('reading_assignments', true);
    }
}
