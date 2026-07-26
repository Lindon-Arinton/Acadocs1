<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Source: php/trainee-data/KPIs/KEY-PERFORMANCE-INDICATOR-*.docx
 * (Matabungkay National High School's official DepEd KPI reports.)
 */
class DepedKpiReportSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('deped_kpi_reports')->insertBatch([
            [
                'school_year' => '2020-2021', 'gross_enrolment_rate' => 64.60, 'net_enrolment_rate' => 52.15,
                'cohort_survival_rate' => 91.90, 'repetition_rate' => 2.79, 'promotion_rate' => 99.60,
                'retention_rate' => 100.00, 'graduation_rate' => 100.00, 'completion_rate' => 91.90,
                'transition_rate' => 75.47, 'dropout_rate' => 0.42,
                'enrolment_total' => null, 'enrolment_male' => null, 'enrolment_female' => null,
                'source_file' => 'KEY-PERFORMANCE-INDICATOR-2020-2021.docx',
            ],
            [
                'school_year' => '2021-2022', 'gross_enrolment_rate' => 63.25, 'net_enrolment_rate' => 52.04,
                'cohort_survival_rate' => 96.20, 'repetition_rate' => 1.83, 'promotion_rate' => 99.61,
                'retention_rate' => 95.74, 'graduation_rate' => 99.05, 'completion_rate' => 99.52,
                'transition_rate' => 81.19, 'dropout_rate' => 0.39,
                'enrolment_total' => null, 'enrolment_male' => null, 'enrolment_female' => null,
                'source_file' => 'KEY-PERFORMANCE-INDICATOR-2021-2022.docx',
            ],
            [
                'school_year' => '2022-2023', 'gross_enrolment_rate' => 69.11, 'net_enrolment_rate' => 62.74,
                'cohort_survival_rate' => 98.10, 'repetition_rate' => 0.90, 'promotion_rate' => 96.54,
                'retention_rate' => 95.74, 'graduation_rate' => 95.73, 'completion_rate' => 99.36,
                'transition_rate' => 73.30, 'dropout_rate' => 2.49,
                'enrolment_total' => null, 'enrolment_male' => null, 'enrolment_female' => null,
                'source_file' => 'KEY-PERFORMANCE-INDICATOR-2022-2023.docx',
            ],
            [
                'school_year' => '2023-2024', 'gross_enrolment_rate' => 64.83, 'net_enrolment_rate' => 61.02,
                'cohort_survival_rate' => 93.81, 'repetition_rate' => 0.82, 'promotion_rate' => 92.83,
                'retention_rate' => 100.00, 'graduation_rate' => 95.00, 'completion_rate' => 95.00,
                'transition_rate' => 67.63, 'dropout_rate' => 2.43,
                'enrolment_total' => 698, 'enrolment_male' => 367, 'enrolment_female' => 331,
                'source_file' => 'KEY-PERFORMANCE-INDICATOR-2023-2024.docx',
            ],
        ]);
    }
}
