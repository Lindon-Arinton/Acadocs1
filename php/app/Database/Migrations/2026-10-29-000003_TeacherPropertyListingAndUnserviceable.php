<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Teachers list their own room property and keep each item's status up to
 * date. The condition is now Serviceable / Unserviceable (was Working /
 * Not Working); marking an item Unserviceable prompts the teacher to request
 * a replacement from the ADAS. `added_by` remembers who listed an item, so a
 * teacher can edit or delete only what they listed (until it is on a PAR).
 * Condition history and PAR snapshots are renamed the same way.
 */
class TeacherPropertyListingAndUnserviceable extends Migration
{
    private const RENAME = ['Working' => 'Serviceable', 'Not Working' => 'Unserviceable'];

    public function up()
    {
        $this->db->query("ALTER TABLE room_properties MODIFY condition_status ENUM('Working','Not Working','Serviceable','Unserviceable') NOT NULL DEFAULT 'Serviceable'");
        $this->rename(self::RENAME);
        $this->db->query("ALTER TABLE room_properties MODIFY condition_status ENUM('Serviceable','Unserviceable') NOT NULL DEFAULT 'Serviceable'");

        $this->db->query('ALTER TABLE room_properties ADD added_by INT UNSIGNED NULL DEFAULT NULL AFTER uploaded_by');
        $this->db->query('ALTER TABLE room_properties ADD KEY added_by (added_by)');
        $this->db->query('ALTER TABLE room_properties ADD CONSTRAINT room_properties_added_by_fk FOREIGN KEY (added_by) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE room_properties DROP FOREIGN KEY room_properties_added_by_fk');
        $this->db->query('ALTER TABLE room_properties DROP COLUMN added_by');

        $this->db->query("ALTER TABLE room_properties MODIFY condition_status ENUM('Working','Not Working','Serviceable','Unserviceable') NOT NULL DEFAULT 'Working'");
        $this->rename(array_flip(self::RENAME));
        $this->db->query("ALTER TABLE room_properties MODIFY condition_status ENUM('Working','Not Working') NOT NULL DEFAULT 'Working'");
    }

    /** @param array<string,string> $map old => new */
    private function rename(array $map): void
    {
        foreach ($map as $from => $to) {
            $this->db->query('UPDATE room_properties SET condition_status = ? WHERE condition_status = ?', [$to, $from]);
            $this->db->query('UPDATE property_condition_logs SET from_status = ? WHERE from_status = ?', [$to, $from]);
            $this->db->query('UPDATE property_condition_logs SET to_status = ? WHERE to_status = ?', [$to, $from]);
            $this->db->query('UPDATE property_acknowledgement_items SET condition_status = ? WHERE condition_status = ?', [$to, $from]);
        }
    }
}
