<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateConversationTyping extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'conversation_id' => ['type' => 'INT', 'unsigned' => true],
            'user_id'         => ['type' => 'INT', 'unsigned' => true],
            // Set explicitly on every upsert (see ConversationTypingModel::ping()) rather
            // than relying on an ON UPDATE CURRENT_TIMESTAMP clause here, matching how
            // ConversationParticipantModel::markRead() already sets its own timestamp —
            // keeps the "current time" source consistently DB-generated (NOW()) either way.
            'updated_at'      => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['conversation_id', 'user_id']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('conversation_id', 'conversations', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE');
        $this->forge->createTable('conversation_typing');
    }

    public function down()
    {
        $this->forge->dropTable('conversation_typing', true);
    }
}
