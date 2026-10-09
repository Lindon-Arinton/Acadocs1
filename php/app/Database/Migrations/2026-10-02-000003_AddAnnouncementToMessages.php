<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A chat message can carry a shared announcement (rendered as a card in the
 * chat). The message body keeps a plain-text fallback ("📢 Title …"), so if
 * the announcement is later deleted the link is simply cleared.
 */
class AddAnnouncementToMessages extends Migration
{
    public function up()
    {
        $this->forge->addColumn('messages', [
            'announcement_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'attachment_ext'],
        ]);
        $this->db->query('ALTER TABLE messages ADD CONSTRAINT messages_announcement_fk FOREIGN KEY (announcement_id) REFERENCES announcements (id) ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE messages DROP FOREIGN KEY messages_announcement_fk');
        $this->forge->dropColumn('messages', 'announcement_id');
    }
}
