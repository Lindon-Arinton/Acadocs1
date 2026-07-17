<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateDocumentLinks extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'category' => ['type' => 'ENUM', 'constraint' => ['Forms', 'Questionnaires', 'Templates', 'Guidelines']],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'url' => ['type' => 'VARCHAR', 'constraint' => 500],
            'added_by' => ['type' => 'VARCHAR', 'constraint' => 100],
            'date_added' => ['type' => 'DATE'],
            'access_level' => ['type' => 'ENUM', 'constraint' => ['All Users', 'Teachers', 'Admin'], 'default' => 'All Users'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('document_links');
    }

    public function down()
    {
        $this->forge->dropTable('document_links', true);
    }
}
