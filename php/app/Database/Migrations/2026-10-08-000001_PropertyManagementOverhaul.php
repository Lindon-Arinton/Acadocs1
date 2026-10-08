<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Property Management rework (panel feedback):
 *  - Condition is now Serviceable / Non-serviceable (Excellent/Good/Fair map
 *    to Serviceable, Poor to Non-serviceable).
 *  - Each item records how it was acquired (New / Donated / Second-hand /
 *    Other + specify), free-form notes, and who it is issued to (the
 *    accountable teacher).
 *  - Every condition change is logged so reports show when and why an item
 *    became non-serviceable.
 *  - ADAS issues a Property Acknowledgment Receipt (PAR) for a teacher's
 *    items; the teacher approves it or returns it with remarks. PAR items
 *    are a snapshot, so the receipt stays accurate after items change.
 */
class PropertyManagementOverhaul extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('room_properties', [
            'condition_status' => ['type' => 'ENUM', 'constraint' => ['Excellent', 'Good', 'Fair', 'Poor', 'Serviceable', 'Non-serviceable'], 'null' => false],
        ]);
        $this->db->query("UPDATE room_properties SET condition_status = 'Serviceable' WHERE condition_status IN ('Excellent', 'Good', 'Fair')");
        $this->db->query("UPDATE room_properties SET condition_status = 'Non-serviceable' WHERE condition_status = 'Poor'");
        $this->forge->modifyColumn('room_properties', [
            'condition_status' => ['type' => 'ENUM', 'constraint' => ['Serviceable', 'Non-serviceable'], 'null' => false, 'default' => 'Serviceable'],
        ]);

        $this->forge->addField([
            'id'           =>['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'par_no'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'issued_to'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'issued_by'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'status'       => ['type' => 'ENUM', 'constraint' => ['Pending', 'Approved', 'Returned'], 'default' => 'Pending'],
            'remarks'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'   => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            'responded_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('par_no');
        $this->forge->addKey('issued_to');
        $this->forge->addForeignKey('issued_to', 'users', 'id', '', 'CASCADE', 'property_ack_issued_to_fk');
        $this->forge->addForeignKey('issued_by', 'users', 'id', '', 'SET NULL', 'property_ack_issued_by_fk');
        $this->forge->createTable('property_acknowledgements', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addColumn('room_properties', [
            'acquisition_type'  => ['type' => 'ENUM', 'constraint' => ['New', 'Donated', 'Second-hand', 'Other'], 'default' => 'New', 'after' => 'condition_status'],
            'acquisition_other' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'acquisition_type'],
            'notes'             => ['type' => 'TEXT', 'null' => true, 'after' => 'acquisition_other'],
            'issued_to'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'notes'],
            'par_id'            => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'issued_to'],
            'updated_at'        => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $this->db->query('ALTER TABLE room_properties ADD KEY issued_to (issued_to), ADD KEY par_id (par_id)');
        $this->db->query('ALTER TABLE room_properties ADD CONSTRAINT room_properties_issued_to_fk FOREIGN KEY (issued_to) REFERENCES users (id) ON DELETE SET NULL');
        $this->db->query('ALTER TABLE room_properties ADD CONSTRAINT room_properties_par_fk FOREIGN KEY (par_id) REFERENCES property_acknowledgements (id) ON DELETE SET NULL');

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'par_id'          => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'property_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'item_name'       => ['type' => 'VARCHAR', 'constraint' => 150],
            'grade'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'section'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'quantity'        => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 1],
            'condition_status' => ['type' => 'VARCHAR', 'constraint' => 30],
            'acquisition'     => ['type' => 'VARCHAR', 'constraint' => 170],
            'date_acquired'   => ['type' => 'DATE', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('par_id');
        $this->forge->addForeignKey('par_id', 'property_acknowledgements', 'id', '', 'CASCADE', 'property_ack_items_par_fk');
        $this->forge->addForeignKey('property_id', 'room_properties', 'id', '', 'SET NULL', 'property_ack_items_property_fk');
        $this->forge->createTable('property_acknowledgement_items', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'property_id'     => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'from_status'     => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'to_status'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'remarks'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
            'updated_by_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at'      => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('property_id');
        $this->forge->addForeignKey('property_id', 'room_properties', 'id', '', 'CASCADE', 'property_condition_logs_property_fk');
        $this->forge->addForeignKey('updated_by', 'users', 'id', '', 'SET NULL', 'property_condition_logs_user_fk');
        $this->forge->createTable('property_condition_logs', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down()
    {
        $this->forge->dropTable('property_condition_logs', true);
        $this->forge->dropTable('property_acknowledgement_items', true);
        $this->db->query('ALTER TABLE room_properties DROP FOREIGN KEY room_properties_par_fk, DROP FOREIGN KEY room_properties_issued_to_fk');
        $this->forge->dropColumn('room_properties', ['acquisition_type', 'acquisition_other', 'notes', 'issued_to', 'par_id', 'updated_at']);
        $this->forge->dropTable('property_acknowledgements', true);

        $this->forge->modifyColumn('room_properties', [
            'condition_status' => ['type' => 'ENUM', 'constraint' => ['Excellent', 'Good', 'Fair', 'Poor', 'Serviceable', 'Non-serviceable'], 'null' => false],
        ]);
        $this->db->query("UPDATE room_properties SET condition_status = 'Good' WHERE condition_status = 'Serviceable'");
        $this->db->query("UPDATE room_properties SET condition_status = 'Poor' WHERE condition_status = 'Non-serviceable'");
        $this->forge->modifyColumn('room_properties', [
            'condition_status' => ['type' => 'ENUM', 'constraint' => ['Excellent', 'Good', 'Fair', 'Poor'], 'null' => false],
        ]);
    }
}
