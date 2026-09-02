<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RestructureRoomProperties extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('room_properties', [
            'building_name' => ['name' => 'grade', 'type' => 'VARCHAR', 'constraint' => 100],
            'room_number'   => ['name' => 'section', 'type' => 'VARCHAR', 'constraint' => 50],
        ]);
        $this->forge->dropColumn('room_properties', ['quantity', 'last_inspection', 'remarks']);
    }

    public function down()
    {
        $this->forge->addColumn('room_properties', [
            'quantity'        => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'last_inspection' => ['type' => 'DATE', 'null' => true],
            'remarks'         => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->modifyColumn('room_properties', [
            'grade'   => ['name' => 'building_name', 'type' => 'VARCHAR', 'constraint' => 100],
            'section' => ['name' => 'room_number', 'type' => 'VARCHAR', 'constraint' => 50],
        ]);
    }
}
