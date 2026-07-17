<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DocumentLinkSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('document_links')->insertBatch([
            ['category' => 'Forms',          'title' => 'Teacher Performance Evaluation Form', 'description' => 'Annual performance evaluation form for all teaching staff', 'url' => 'https://docs.google.com/forms/d/sample-form-1',           'added_by' => 'Carmen Lopez', 'date_added' => '2026-03-15', 'access_level' => 'All Users'],
            ['category' => 'Questionnaires', 'title' => 'Student Satisfaction Survey',         'description' => 'Term survey to assess student satisfaction and needs',     'url' => 'https://docs.google.com/forms/d/sample-survey-1',         'added_by' => 'Carmen Lopez', 'date_added' => '2026-03-20', 'access_level' => 'All Users'],
            ['category' => 'Templates',      'title' => 'DLL Template 2026',                   'description' => 'Official Daily Lesson Log template for SY 2025-2026',      'url' => 'https://docs.google.com/spreadsheets/d/sample-dll-template', 'added_by' => 'Carmen Lopez', 'date_added' => '2026-01-10', 'access_level' => 'Teachers'],
            ['category' => 'Guidelines',     'title' => 'DepEd Memorandum 023-2026',           'description' => 'Latest guidelines on curriculum implementation',           'url' => 'https://drive.google.com/file/d/sample-memo-1',           'added_by' => 'Carmen Lopez', 'date_added' => '2026-02-28', 'access_level' => 'All Users'],
            ['category' => 'Forms',          'title' => 'Leave Application Form',              'description' => 'Standard leave application form for all personnel',        'url' => 'https://docs.google.com/forms/d/sample-leave-form',       'added_by' => 'Carmen Lopez', 'date_added' => '2026-01-05', 'access_level' => 'All Users'],
        ]);
    }
}
