<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSchoolFunds extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'date' => ['type' => 'DATE'],
            'category' => ['type' => 'ENUM', 'constraint' => ['MOOE', 'Capital Outlay', 'Maintenance', 'Other']],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255],
            'particulars' => ['type' => 'TEXT', 'null' => true],
            'amount' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'balance' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'prepared_by' => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('school_funds');
    }

    public function down()
    {
        $this->forge->dropTable('school_funds', true);
    }
}
