<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddQuantityAndUploadedByToRoomProperties extends Migration
{
    public function up()
    {
        $this->forge->addColumn('room_properties', [
            'quantity'    => ['type' => 'INT', 'unsigned' => true, 'default' => 1, 'after' => 'item_name'],
            'uploaded_by' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'condition_status'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('room_properties', ['quantity', 'uploaded_by']);
    }
}
