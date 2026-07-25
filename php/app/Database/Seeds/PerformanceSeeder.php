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

        // No demo per-subject performance rows — the old ones named fake teachers.
    }
}
