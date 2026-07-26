<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateDocumentFiles extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'document_id' => ['type' => 'INT', 'unsigned' => true],
            'file_path' => ['type' => 'VARCHAR', 'constraint' => 500],
            'file_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('document_id');
        $this->forge->addForeignKey('document_id', 'documents', 'id', '', 'CASCADE');
        $this->forge->createTable('document_files');
    }

    public function down()
    {
        $this->forge->dropTable('document_files', true);
    }
}
