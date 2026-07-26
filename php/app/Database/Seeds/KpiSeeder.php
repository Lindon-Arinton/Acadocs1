<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Source: php/trainee-data/Enrollment list/Matabungkay-NHS-SMEPA-2025.pptx
 * (Matabungkay National High School's SMEPA report, school year 2024-2025.)
 * submission_compliance is a placeholder here — the app computes it live
 * from the real `documents` table at request time (DocumentModel::submissionComplianceRate()).
 */
class KpiSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('kpi_snapshots')->insert([
            'school_year' => '2024-2025', 'total_enrollment' => 714, 'submission_compliance' => 0,
            'average_mps' => 69.89, 'dropout_count' => 10, 'parent_attendance' => 74.00,
        ]);

        $this->db->table('enrollment_by_level')->insertBatch([
            ['school_year' => '2023-2024', 'grade_level' => 'Grade 7',  'students' => 155, 'sections' => 4],
            ['school_year' => '2023-2024', 'grade_level' => 'Grade 8',  'students' => 166, 'sections' => 5],
            ['school_year' => '2023-2024', 'grade_level' => 'Grade 9',  'students' => 180, 'sections' => 5],
            ['school_year' => '2023-2024', 'grade_level' => 'Grade 10', 'students' => 180, 'sections' => 5],
            ['school_year' => '2024-2025', 'grade_level' => 'Grade 7',  'students' => 228, 'sections' => 6],
            ['school_year' => '2024-2025', 'grade_level' => 'Grade 8',  'students' => 149, 'sections' => 4],
            ['school_year' => '2024-2025', 'grade_level' => 'Grade 9',  'students' => 153, 'sections' => 4],
            ['school_year' => '2024-2025', 'grade_level' => 'Grade 10', 'students' => 184, 'sections' => 5],
        ]);
    }
}
