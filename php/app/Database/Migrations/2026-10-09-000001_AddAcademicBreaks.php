<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Academic breaks: date ranges with no classes (seeded with SY 2026-2027's
 * Christmas and EOSY breaks), plus an "Academic Break" attendance status so
 * school days inside one aren't recorded as absences.
 */
class AddAcademicBreaks extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 150],
            'start_date' => ['type' => 'DATE'],
            'end_date'   => ['type' => 'DATE'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['start_date', 'end_date']);
        $this->forge->createTable('academic_breaks');

        $this->db->table('academic_breaks')->insertBatch([
            ['label' => 'Christmas Break', 'start_date' => '2026-12-19', 'end_date' => '2027-01-03'],
            ['label' => 'EOSY Break',      'start_date' => '2027-04-09', 'end_date' => '2027-05-09'],
        ]);

        $this->db->query("ALTER TABLE time_records MODIFY status ENUM('Present','Late','Absent','On Leave','Academic Break') NOT NULL");
    }

    public function down()
    {
        $this->db->query("UPDATE time_records SET status = 'Absent' WHERE status = 'Academic Break'");
        $this->db->query("ALTER TABLE time_records MODIFY status ENUM('Present','Late','Absent','On Leave') NOT NULL");
        $this->forge->dropTable('academic_breaks', true);
    }
}
