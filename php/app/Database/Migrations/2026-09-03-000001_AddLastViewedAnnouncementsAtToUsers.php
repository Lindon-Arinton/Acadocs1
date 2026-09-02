<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLastViewedAnnouncementsAtToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'last_viewed_announcements_at' => ['type' => 'TIMESTAMP', 'null' => true, 'after' => 'last_active_at'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'last_viewed_announcements_at');
    }
}
