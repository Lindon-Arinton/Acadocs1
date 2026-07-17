<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateDepedDocuments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'document_type' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255],
            'due_date' => ['type' => 'DATE'],
            'status' => ['type' => 'ENUM', 'constraint' => ['Pending', 'In Progress', 'Completed'], 'default' => 'Pending'],
            'completion_rate' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'prepared_by' => ['type' => 'VARCHAR', 'constraint' => 100],
            'last_updated' => ['type' => 'DATE'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('deped_documents');
    }

    public function down()
    {
        $this->forge->dropTable('deped_documents', true);
    }
}
