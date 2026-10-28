<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Optional folders inside a Templates section, which can nest (folders within
 * folders), so the ADAS can organize a large section — e.g. "HRD Forms" →
 * "Leave" → "2026". A template with no folder sits at the section's top level.
 * Folders only organize the list; files stay in uploads/templates/{section}.
 */
class AddTemplateFolders extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'category_id' => ['type' => 'INT', 'unsigned' => true],
            'parent_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 150],
            'created_by'  => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at'  => ['type' => 'TIMESTAMP', 'null' => true, 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('category_id');
        $this->forge->addKey('parent_id');
        $this->forge->addForeignKey('category_id', 'template_categories', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('parent_id', 'template_folders', 'id', '', 'CASCADE');
        $this->forge->createTable('template_folders');

        $this->forge->addColumn('templates', [
            'folder_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'category_id'],
        ]);
        $this->db->query('ALTER TABLE templates ADD KEY folder_id (folder_id)');
        $this->db->query('ALTER TABLE templates ADD CONSTRAINT templates_folder_fk FOREIGN KEY (folder_id) REFERENCES template_folders (id) ON DELETE SET NULL');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE templates DROP FOREIGN KEY templates_folder_fk');
        $this->forge->dropColumn('templates', ['folder_id']);
        $this->forge->dropTable('template_folders', true);
    }
}
