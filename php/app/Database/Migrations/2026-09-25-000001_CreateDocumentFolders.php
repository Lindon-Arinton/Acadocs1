<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One Document Management folder per task, auto-created on the first upload
 * to that task. Files stay in task_submission_files — the folder only groups
 * them — so deleting the task cascades the folder away with its submissions.
 */
class CreateDocumentFolders extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'auto_increment' => true],
            'task_id'    => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 200],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('task_id');
        $this->forge->addForeignKey('task_id', 'tasks', 'id', '', 'CASCADE', 'document_folders_task_fk');
        $this->forge->createTable('document_folders', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);

        // Backfill folders for tasks that already have uploads.
        $this->db->query(
            "INSERT IGNORE INTO document_folders (task_id, name)
             SELECT t.id, CONCAT(t.title, ' - ', DATE_FORMAT(t.created_at, '%b %d, %Y'))
             FROM tasks t
             WHERE EXISTS (SELECT 1 FROM task_submissions s WHERE s.task_id = t.id)"
        );
    }

    public function down()
    {
        $this->forge->dropTable('document_folders', true);
    }
}
