<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateCanteenRecords extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'date' => ['type' => 'DATE'],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255],
            'revenue' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'expenses' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00],
            'transaction_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('canteen_records');

        // GENERATED ALWAYS AS column has no Forge shorthand — added via raw SQL.
        $this->db->query(
            'ALTER TABLE canteen_records ADD COLUMN net_income DECIMAL(12,2) GENERATED ALWAYS AS (revenue - expenses) STORED AFTER expenses'
        );
    }

    public function down()
    {
        $this->forge->dropTable('canteen_records', true);
    }
}
