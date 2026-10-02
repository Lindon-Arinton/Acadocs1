<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Time records used to find their owner only through the biometric number
 * (employee_id = "AC-{users.ac_no}"). If a number was changed or reused, the
 * history detached from (or attached to the wrong) person. user_id pins each
 * record to the account it belonged to when it was imported; employee_id
 * stays as the scanner's own reference.
 *
 * Backfilled from the current AC-No assignments.
 */
class AddUserIdToTimeRecords extends Migration
{
    public function up()
    {
        $this->forge->addColumn('time_records', [
            'user_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'employee_id'],
        ]);
        $this->db->query('ALTER TABLE time_records ADD KEY time_records_user_id (user_id), ADD CONSTRAINT time_records_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->db->query("UPDATE time_records tr JOIN users u ON u.ac_no IS NOT NULL AND u.ac_no <> '' AND tr.employee_id = CONCAT('AC-', u.ac_no) SET tr.user_id = u.id");
    }

    public function down()
    {
        $this->db->query('ALTER TABLE time_records DROP FOREIGN KEY time_records_user_fk');
        $this->db->query('ALTER TABLE time_records DROP KEY time_records_user_id');
        $this->forge->dropColumn('time_records', 'user_id');
    }
}
