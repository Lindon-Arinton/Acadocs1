<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PerformanceSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('performance_by_level')->insertBatch([
            ['school_year' => '2025-2026', 'grade_level' => 'Grade 7',  'mps' => 84.20, 'nds' => 78.50],
            ['school_year' => '2025-2026', 'grade_level' => 'Grade 8',  'mps' => 82.70, 'nds' => 76.30],
            ['school_year' => '2025-2026', 'grade_level' => 'Grade 9',  'mps' => 80.10, 'nds' => 74.80],
            ['school_year' => '2025-2026', 'grade_level' => 'Grade 10', 'mps' => 83.50, 'nds' => 79.20],
        ]);

        $this->db->table('performance_by_subject')->insertBatch([
            ['school_year' => '2025-2026', 'subject' => 'Mathematics', 'grade_level' => 'Grade 7',  'instructor' => 'Maria Santos',   'mps' => 76.50],
            ['school_year' => '2025-2026', 'subject' => 'Science',     'grade_level' => 'Grade 7',  'instructor' => 'Juan dela Cruz', 'mps' => 85.20],
            ['school_year' => '2025-2026', 'subject' => 'English',     'grade_level' => 'Grade 8',  'instructor' => 'Ana Reyes',      'mps' => 83.70],
            ['school_year' => '2025-2026', 'subject' => 'Filipino',    'grade_level' => 'Grade 8',  'instructor' => 'Pedro Garcia',   'mps' => 88.10],
            ['school_year' => '2025-2026', 'subject' => 'Mathematics', 'grade_level' => 'Grade 9',  'instructor' => 'Rosa Mendoza',   'mps' => 74.30],
            ['school_year' => '2025-2026', 'subject' => 'Science',     'grade_level' => 'Grade 9',  'instructor' => 'Carlos Bautista','mps' => 81.90],
            ['school_year' => '2025-2026', 'subject' => 'English',     'grade_level' => 'Grade 10', 'instructor' => 'Sofia Torres',   'mps' => 86.40],
            ['school_year' => '2025-2026', 'subject' => 'Filipino',    'grade_level' => 'Grade 10', 'instructor' => 'Miguel Ramos',   'mps' => 89.20],
        ]);
    }
}
