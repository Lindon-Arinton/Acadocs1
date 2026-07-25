<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUniqueEmployeeDateToTimeRecords extends Migration
{
    public function up()
    {
        // Keep the earliest row per (employee_id, date) so the new unique
        // key below doesn't fail against pre-existing duplicate rows.
        $this->db->query(
            'DELETE t1 FROM time_records t1
             INNER JOIN time_records t2
                ON t1.employee_id = t2.employee_id
               AND t1.date = t2.date
               AND t1.id > t2.id'
        );

        $this->forge->addUniqueKey(['employee_id', 'date']);
        $this->forge->processIndexes('time_records');
    }

    public function down()
    {
        $this->forge->dropKey('time_records', 'time_records_employee_id_date', false);
    }
}
