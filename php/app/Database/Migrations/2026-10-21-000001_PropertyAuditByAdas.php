<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Property Management, ADAS-run:
 *  - Condition is now Working / Not Working (was Serviceable / Non-serviceable),
 *    including the PAR snapshots and condition history.
 *  - ADAS audits items: last_checked_at / last_checked_by record the latest check.
 *  - Items belong to the adviser of their section; unassigned items are
 *    backfilled to that adviser.
 *  - Teachers report concerns about their items (property_concerns); ADAS
 *    resolves them.
 */
class PropertyAuditByAdas extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('room_properties', [
            'condition_status' => ['type' => 'ENUM', 'constraint' => ['Serviceable', 'Non-serviceable', 'Working', 'Not Working'], 'null' => false],
        ]);
        $this->renameConditions(['Serviceable' => 'Working', 'Non-serviceable' => 'Not Working']);
        $this->forge->modifyColumn('room_properties', [
            'condition_status' => ['type' => 'ENUM', 'constraint' => ['Working', 'Not Working'], 'null' => false, 'default' => 'Working'],
        ]);

        $this->forge->addColumn('room_properties', [
            'last_checked_at' => ['type' => 'TIMESTAMP', 'null' => true, 'after' => 'par_id'],
            'last_checked_by' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'last_checked_at'],
        ]);

        // Give unassigned items to their section's adviser (not the co-adviser).
        $this->db->query(
            "UPDATE room_properties rp
             JOIN teachers t ON LOWER(TRIM(t.advisory)) = LOWER(TRIM(rp.section))
             JOIN users u ON u.id = t.user_id AND u.role = 'teacher'
             SET rp.issued_to = u.id
             WHERE rp.issued_to IS NULL"
        );

        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'property_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'reported_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'category'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'details'     => ['type' => 'TEXT'],
            'status'      => ['type' => 'ENUM', 'constraint' => ['Open', 'Resolved'], 'default' => 'Open'],
            'response'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'resolved_by' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'resolved_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'created_at'  => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('property_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('property_id', 'room_properties', 'id', '', 'CASCADE', 'property_concerns_property_fk');
        $this->forge->addForeignKey('reported_by', 'users', 'id', '', 'SET NULL', 'property_concerns_reporter_fk');
        $this->forge->addForeignKey('resolved_by', 'users', 'id', '', 'SET NULL', 'property_concerns_resolver_fk');
        $this->forge->createTable('property_concerns', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('property_concerns', true);
        $this->forge->dropColumn('room_properties', ['last_checked_at', 'last_checked_by']);

        $this->forge->modifyColumn('room_properties', [
            'condition_status' => ['type' => 'ENUM', 'constraint' => ['Serviceable', 'Non-serviceable', 'Working', 'Not Working'], 'null' => false],
        ]);
        $this->renameConditions(['Working' => 'Serviceable', 'Not Working' => 'Non-serviceable']);
        $this->forge->modifyColumn('room_properties', [
            'condition_status' => ['type' => 'ENUM', 'constraint' => ['Serviceable', 'Non-serviceable'], 'null' => false, 'default' => 'Serviceable'],
        ]);
    }

    /** @param array<string,string> $map old => new */
    private function renameConditions(array $map): void
    {
        foreach ($map as $old => $new) {
            $this->db->table('room_properties')->where('condition_status', $old)->update(['condition_status' => $new]);
            $this->db->table('property_acknowledgement_items')->where('condition_status', $old)->update(['condition_status' => $new]);
            $this->db->table('property_condition_logs')->where('from_status', $old)->update(['from_status' => $new]);
            $this->db->table('property_condition_logs')->where('to_status', $old)->update(['to_status' => $new]);
        }
    }
}
