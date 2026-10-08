<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Document Management folders can now nest, like a file explorer: admins
 * create plain folders (no task — e.g. "School Forms") and move task folders
 * (e.g. "SF3 Term 1", "SF3 Term 2") or other folders into them.
 */
class AddParentToDocumentFolders extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('document_folders', [
            'task_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true],
        ]);
        $this->forge->addColumn('document_folders', [
            'parent_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => true, 'after' => 'task_id'],
        ]);
        $this->db->query('ALTER TABLE document_folders ADD KEY parent_id (parent_id)');
        $this->db->query('ALTER TABLE document_folders ADD CONSTRAINT document_folders_parent_fk FOREIGN KEY (parent_id) REFERENCES document_folders (id) ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE document_folders DROP FOREIGN KEY document_folders_parent_fk');
        $this->forge->dropColumn('document_folders', ['parent_id']);
        $this->db->query('DELETE FROM document_folders WHERE task_id IS NULL');
        $this->forge->modifyColumn('document_folders', [
            'task_id' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'null' => false],
        ]);
    }
}
