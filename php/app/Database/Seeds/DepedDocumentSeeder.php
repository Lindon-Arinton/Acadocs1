<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DepedDocumentSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('deped_documents')->insertBatch([
            ['document_type' => 'School Form 1 (SF1)',                    'description' => 'School Register',                          'due_date' => '2026-04-15', 'status' => 'In Progress', 'completion_rate' => 75,  'prepared_by' => 'Jose Ramirez', 'last_updated' => '2026-03-30'],
            ['document_type' => 'School Form 2 (SF2)',                    'description' => 'Daily Attendance Report',                  'due_date' => '2026-04-05', 'status' => 'Completed',   'completion_rate' => 100, 'prepared_by' => 'Jose Ramirez', 'last_updated' => '2026-03-28'],
            ['document_type' => 'School Form 3 (SF3)',                    'description' => 'Individual Learner Monitoring',            'due_date' => '2026-04-20', 'status' => 'In Progress', 'completion_rate' => 60,  'prepared_by' => 'Jose Ramirez', 'last_updated' => '2026-03-29'],
            ['document_type' => 'Learners Enrollment Survey Form (LESF)', 'description' => 'Annual enrollment data submission',        'due_date' => '2026-05-01', 'status' => 'Pending',     'completion_rate' => 25,  'prepared_by' => 'Jose Ramirez', 'last_updated' => '2026-03-25'],
            ['document_type' => 'Annual Implementation Plan (AIP)',       'description' => 'School improvement plan for SY 2026-2027', 'due_date' => '2026-05-15', 'status' => 'Pending',     'completion_rate' => 15,  'prepared_by' => 'Jose Ramirez', 'last_updated' => '2026-03-20'],
        ]);
    }
}
