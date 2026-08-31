<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMutedToConversationParticipants extends Migration
{
    public function up()
    {
        $this->forge->addColumn('conversation_participants', [
            'muted' => ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'last_read_at'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('conversation_participants', 'muted');
    }
}
