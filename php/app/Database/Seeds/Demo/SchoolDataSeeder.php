<?php

namespace App\Database\Seeds\Demo;

use App\Controllers\Teacher\PerformanceMps;
use App\Libraries\MpsCalculator;

/**
 * Admin Dashboard KPIs and Performance Analytics: enrolment per grade, a
 * DepEd KPI report, and per-section MPS scores (rolled up into the
 * performance tables by MpsCalculator, exactly like the Enter MPS Scores
 * page does).
 *
 * Only ever fills school years that have no data of that kind at all, so
 * real reports and real MPS entries are never touched. The official figures
 * in DepedKpiReportSeeder / KpiSeeder / PerformanceSeeder cover up to SY
 * 2024-2025, so demo data starts after that.
 */
class SchoolDataSeeder extends DemoSeeder
{
    /** Years that get demo enrolment when they have none (last year and the current one). */
    private const ENROLMENT_YEARS = ['2025-2026', '2026-2027'];

    /** Completed year that gets a demo KPI report and all three terms of MPS when it has none. */
    private const PAST_YEAR = '2025-2026';

    /** Rough per-subject MPS baselines — Math/Science lowest, ESP/MAPEH highest, like the school's real SMEPA figures. */
    private const SUBJECT_BASE = [
        'Mathematics' => 55, 'Science' => 58, 'English' => 64, 'Filipino' => 69,
        'AP' => 67, 'TLE' => 71, 'MAPEH' => 73, 'ESP' => 77,
    ];

    // teacher_subjects codes -> MPS subjects (same idea as PerformanceMps::SUBJECT_ALIASES; HG/RESEARCH/SPFL aren't MPS-tracked).
    private const SUBJECT_CODES = [
        'MATH' => 'Mathematics', 'SCIENCE' => 'Science', 'ENGLISH' => 'English', 'FILIPINO' => 'Filipino',
        'AP' => 'AP', 'TLE' => 'TLE', 'MAPEH' => 'MAPEH', 'ESP' => 'ESP', 'VE' => 'ESP',
    ];

    public function run()
    {
        $sections = $this->sectionsByGrade();

        foreach (self::ENROLMENT_YEARS as $year) {
            if ($this->db->table('enrollment_by_level')->where('school_year', $year)->countAllResults() === 0) {
                $this->seedEnrolment($year, $sections);
            }
        }

        if ($this->db->table('deped_kpi_reports')->where('school_year', self::PAST_YEAR)->countAllResults() === 0) {
            $this->seedKpiReport(self::PAST_YEAR);
        }

        foreach (PerformanceMps::TERM_OPTIONS as $term) {
            if ($this->hasMpsData(self::PAST_YEAR, $term)) {
                continue;
            }
            (new MpsCalculator())->saveSectionScores(self::PAST_YEAR, $term, $this->mpsEntries($term));
        }
    }

    private function seedEnrolment(string $year, array $sections): void
    {
        $rows = [];
        foreach (PerformanceMps::GRADE_LEVELS as $grade) {
            $sectionCount = max(1, count($sections[$grade] ?? []));
            $students     = $sectionCount * mt_rand(36, 45);
            $male         = (int) round($students * mt_rand(49, 54) / 100);

            $rows[] = [
                'school_year' => $year,
                'grade_level' => $grade,
                'students'    => $students,
                'male'        => $male,
                'female'      => $students - $male,
                'sections'    => $sectionCount,
            ];
        }

        $this->db->table('enrollment_by_level')->insertBatch($rows);
    }

    /** In line with the school's 2020-2025 reports (see DepedKpiReportSeeder). */
    private function seedKpiReport(string $year): void
    {
        $rate = static fn (float $min, float $max) => round($min + mt_rand() / mt_getrandmax() * ($max - $min), 2);

        $this->db->table('deped_kpi_reports')->insert([
            'school_year'          => $year,
            'gross_enrolment_rate' => $rate(64, 70),
            'net_enrolment_rate'   => $rate(58, 63),
            'cohort_survival_rate' => $rate(93, 98),
            'repetition_rate'      => $rate(0.6, 1.2),
            'promotion_rate'       => $rate(93, 98),
            'retention_rate'       => $rate(96, 100),
            'graduation_rate'      => $rate(95, 99),
            'completion_rate'      => $rate(94, 99),
            'transition_rate'      => $rate(68, 78),
            'dropout_rate'         => $rate(1.2, 2.2),
            'source_file'          => 'Demo data (DemoDataSeeder)',
        ]);
    }

    private function hasMpsData(string $year, int $term): bool
    {
        foreach (['mps_test_scores', 'performance_by_subject', 'performance_by_level'] as $table) {
            if ($this->db->table($table)->where('school_year', $year)->where('term', $term)->countAllResults() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * One score per test period for every grade/subject/section some teacher
     * handles, improving a few points each term like the real data does.
     *
     * @return list<array{period:string,grade:string,subject:string,section:?string,mps:float}>
     */
    private function mpsEntries(int $term): array
    {
        $cells = [];
        foreach ($this->db->table('teacher_subjects')->where('school_year', null)->get()->getResultArray() as $row) {
            $code    = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $row['subject']));
            $subject = self::SUBJECT_CODES[$code] ?? null;
            if ($subject === null || ! in_array($row['grade_level'], PerformanceMps::GRADE_LEVELS, true)) {
                continue;
            }
            $cells[$row['grade_level'] . '|' . $subject . '|' . $row['section']] = [$row['grade_level'], $subject, $row['section'] ?: null];
        }

        $entries = [];
        foreach ($cells as [$grade, $subject, $section]) {
            $sectionBase = self::SUBJECT_BASE[$subject] + ($term - 1) * 3 + mt_rand(-6, 6);

            foreach (['Summative Test 1' => -2, 'Summative Test 2' => 1, 'Term Examination' => -4] as $period => $shift) {
                $entries[] = [
                    'period'  => $period,
                    'grade'   => $grade,
                    'subject' => $subject,
                    'section' => $section,
                    'mps'     => round(max(30, min(95, $sectionBase + $shift + mt_rand(-300, 300) / 100)), 2),
                ];
            }
        }

        return $entries;
    }
}
