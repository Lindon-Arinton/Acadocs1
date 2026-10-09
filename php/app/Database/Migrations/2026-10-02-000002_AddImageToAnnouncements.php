<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Optional photo on an announcement (shown as the banner of the announcement
 * pop-up and as the card thumbnail). Stores the file name under
 * public/uploads/announcements/; NULL = no photo (the type icon is shown).
 */
class AddImageToAnnouncements extends Migration
{
    public function up()
    {
        $this->forge->addColumn('announcements', [
            'image' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'content'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('announcements', 'image');
    }
}
