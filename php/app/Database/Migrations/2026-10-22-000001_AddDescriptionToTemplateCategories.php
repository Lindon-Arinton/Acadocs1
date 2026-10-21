<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Templates page shows sections as cards: each section gets a short
 * description (editable by the ADAS). Known sections get a default one.
 */
class AddDescriptionToTemplateCategories extends Migration
{
    private const DEFAULTS = [
        'BAC Forms'                              => 'Bids and Awards Committee forms and documents.',
        'COA Forms'                              => 'Commission on Audit forms and requirements.',
        'CURRICULUM IMPLEMENTATION DIVISION-CID' => 'Curriculum and instructional materials.',
        'Enrollment'                             => 'Enrollment and registration forms.',
        'HRD Forms'                              => 'Human Resource Development forms and documents.',
        'Research Templates'                     => 'Research-related forms and templates.',
        'TRAVEL-RECORDS CHECKLIST'               => 'Travel and official business checklists.',
    ];

    public function up()
    {
        $this->forge->addColumn('template_categories', [
            'description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'name'],
        ]);
        foreach (self::DEFAULTS as $name => $description) {
            $this->db->table('template_categories')->where('name', $name)->where('description', null)->update(['description' => $description]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('template_categories', 'description');
    }
}
