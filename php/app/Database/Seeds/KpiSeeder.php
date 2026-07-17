<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KpiSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('kpi_snapshots')->insert([
            'school_year' => '2025-2026', 'total_enrollment' => 1247, 'submission_compliance' => 87.50,
            'average_mps' => 82.30, 'dropout_count' => 23, 'parent_attendance' => 76.20,
        ]);

        $this->db->table('enrollment_by_level')->insertBatch([
            ['school_year' => '2025-2026', 'grade_level' => 'Grade 7',  'students' => 320, 'sections' => 8],
            ['school_year' => '2025-2026', 'grade_level' => 'Grade 8',  'students' => 315, 'sections' => 8],
            ['school_year' => '2025-2026', 'grade_level' => 'Grade 9',  'students' => 307, 'sections' => 8],
            ['school_year' => '2025-2026', 'grade_level' => 'Grade 10', 'students' => 305, 'sections' => 8],
        ]);
    }
}
