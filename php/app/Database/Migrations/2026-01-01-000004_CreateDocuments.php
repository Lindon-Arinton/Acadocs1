<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateDocuments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'teacher_id' => ['type' => 'INT', 'unsigned' => true],
            'type' => ['type' => 'ENUM', 'constraint' => ['DLL', 'Lesson Plan', 'Assessment', 'Report']],
            'subject' => ['type' => 'VARCHAR', 'constraint' => 100],
            'grade_level' => ['type' => 'VARCHAR', 'constraint' => 50],
            'date_submitted' => ['type' => 'DATETIME'],
            'status' => ['type' => 'ENUM', 'constraint' => ['Submitted', 'Reviewed', 'Returned', 'Pending'], 'default' => 'Pending'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', '', 'CASCADE');
        $this->forge->createTable('documents');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'document_id' => ['type' => 'INT', 'unsigned' => true],
            'author' => ['type' => 'VARCHAR', 'constraint' => 100],
            'comment' => ['type' => 'TEXT'],
            'date' => ['type' => 'DATE'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('document_id', 'documents', 'id', '', 'CASCADE');
        $this->forge->createTable('document_feedback');
    }

    public function down()
    {
        $this->forge->dropTable('document_feedback', true);
        $this->forge->dropTable('documents', true);
    }
}
