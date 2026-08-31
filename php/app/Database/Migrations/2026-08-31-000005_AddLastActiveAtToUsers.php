<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLastActiveAtToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'last_active_at' => ['type' => 'TIMESTAMP', 'null' => true, 'after' => 'photo'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'last_active_at');
    }
}
