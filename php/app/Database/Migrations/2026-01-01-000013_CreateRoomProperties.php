<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateRoomProperties extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'room_number' => ['type' => 'VARCHAR', 'constraint' => 50],
            'building_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'item_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'quantity' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'condition_status' => ['type' => 'ENUM', 'constraint' => ['Excellent', 'Good', 'Fair', 'Poor']],
            'last_inspection' => ['type' => 'DATE'],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('room_properties');
    }

    public function down()
    {
        $this->forge->dropTable('room_properties', true);
    }
}
