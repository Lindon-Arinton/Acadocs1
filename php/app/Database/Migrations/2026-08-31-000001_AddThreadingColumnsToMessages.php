<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddThreadingColumnsToMessages extends Migration
{
    public function up()
    {
        $this->forge->addColumn('messages', [
            'reply_to_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'sender_id'],
            'edited_at'   => ['type' => 'TIMESTAMP', 'null' => true, 'after' => 'created_at'],
            'deleted_at'  => ['type' => 'TIMESTAMP', 'null' => true, 'after' => 'edited_at'],
        ]);

        $this->forge->addForeignKey('reply_to_id', 'messages', 'id', '', 'SET NULL', 'messages_reply_to_fk');
        $this->forge->processIndexes('messages');
    }

    public function down()
    {
        $this->forge->dropForeignKey('messages', 'messages_reply_to_fk');
        $this->forge->dropColumn('messages', ['reply_to_id', 'edited_at', 'deleted_at']);
    }
}
