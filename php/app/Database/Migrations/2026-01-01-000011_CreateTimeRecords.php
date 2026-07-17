<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateTimeRecords extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'date' => ['type' => 'DATE'],
            'employee_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'employee_id' => ['type' => 'VARCHAR', 'constraint' => 20],
            'time_in' => ['type' => 'TIME', 'null' => true],
            'time_out' => ['type' => 'TIME', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['Present', 'Late', 'Absent', 'On Leave']],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('time_records');
    }

    public function down()
    {
        $this->forge->dropTable('time_records', true);
    }
}
