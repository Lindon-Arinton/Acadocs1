<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateTaskFeedback extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'task_submission_id' => ['type' => 'INT', 'unsigned' => true],
            'comment' => ['type' => 'TEXT'],
            'date' => ['type' => 'DATE'],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('task_submission_id', 'task_submissions', 'id', '', 'CASCADE');
        $this->forge->createTable('task_feedback');
    }

    public function down()
    {
        $this->forge->dropTable('task_feedback', true);
    }
}
