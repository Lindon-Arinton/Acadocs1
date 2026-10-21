<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Auto-tagging for Templates (App\Libraries\TemplateClassifier):
 *  - tags: comma-separated labels shown on each template (editable by ADAS).
 *  - keywords: distinctive words from the document's text, used for search
 *    and to learn each section's vocabulary for auto-classification.
 * Fill existing rows with: php spark templates:autotag
 */
class AddTagsToTemplates extends Migration
{
    public function up()
    {
        $this->forge->addColumn('templates', [
            'tags'     => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true, 'after' => 'description'],
            'keywords' => ['type' => 'TEXT', 'null' => true, 'after' => 'tags'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('templates', ['tags', 'keywords']);
    }
}
