<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateTaskSubmissionFiles extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'task_submission_id' => ['type' => 'INT', 'unsigned' => true],
            'file_path' => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('task_submission_id');
        $this->forge->addForeignKey('task_submission_id', 'task_submissions', 'id', '', 'CASCADE');
        $this->forge->createTable('task_submission_files');

        $this->forge->modifyColumn('task_submissions', [
            'file_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'file_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
        ]);
    }

    public function down()
    {
        $this->forge->dropTable('task_submission_files', true);
    }
}
