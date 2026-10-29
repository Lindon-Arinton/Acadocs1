<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Teacher\PerformanceMps;
use App\Libraries\SchoolCalendar;
use App\Models\MpsTestScoreModel;
use App\Models\TeacherModel;

/**
 * MPS Summary for the principal: for each test period (Summative Test 1,
 * Summative Test 2, Term Examination) a Grade 7–10 × subject table of MPS,
 * laid out like the school's own summary sheet. Each grade+subject value is
 * the average of its sections; the breakdown below lists every section's raw
 * MPS per subject, with the teacher who handles it.
 */
class MpsSummary extends BaseController
{
    /** Column order of the school's summary sheet. */
    public const SUBJECTS = ['English', 'Filipino', 'Science', 'Mathematics', 'AP', 'TLE', 'MAPEH', 'Music', 'Arts', 'PE', 'Health', 'ESP'];

    public function index()
    {
        if (! hasRole('admin', 'adas')) {
            return redirect()->to('/dashboard');
        }

        $years = $this->yearsWithScores();
        $today = (new SchoolCalendar())->termOn();

        $year = trim((string) $this->request->getGet('year'));
        if (! preg_match('/^\d{4}-\d{4}$/', $year)) {
            $year = $today['schoolYear'] ?? ($years[0] ?? PerformanceMps::YEAR_OPTIONS[0]);
        }
        $term = (int) ($this->request->getGet('term') ?: ($today['term'] ?? 1));
        if (! in_array($term, PerformanceMps::TERM_OPTIONS, true)) {
            $term = 1;
        }
        if (! in_array($year, $years, true)) {
            $years[] = $year;
            rsort($years);
        }

        $periodKeys = array_flip(PerformanceMps::PERIOD_MAP); // label => s1/s2/exam
        $sections   = []; // subject => grade => sectionLabel => [section, s1, s2, exam]
        foreach ((new MpsTestScoreModel())->forYearTerm($year, $term) as $row) {
            $key = $periodKeys[$row['test_period']] ?? null;
            // 0 means "not filled in" (same rule as the teacher pages).
            if ($key === null || (float) $row['mps'] <= 0) {
                continue;
            }
            $label = $row['section'] ?? PerformanceMps::NO_SECTION;
            $sections[$row['subject']][$row['grade_level']][$label] ??= ['section' => $row['section'], 's1' => null, 's2' => null, 'exam' => null];
            $sections[$row['subject']][$row['grade_level']][$label][$key] = (float) $row['mps'];
        }

        // Summary grid: period => grade => subject => average of its sections.
        $grid = [];
        foreach ($sections as $subject => $byGrade) {
            foreach ($byGrade as $grade => $bySection) {
                foreach (array_keys(PerformanceMps::PERIOD_MAP) as $key) {
                    $values = array_filter(array_column($bySection, $key), static fn ($v) => $v !== null);
                    if ($values !== []) {
                        $grid[$key][$grade][$subject] = ['mps' => round(array_sum($values) / count($values), 2), 'sections' => count($values)];
                    }
                }
            }
        }

        // The sheet's 12 subjects, then any other subject that has scores.
        $extra = array_diff(array_keys($sections), self::SUBJECTS);
        sort($extra, SORT_NATURAL | SORT_FLAG_CASE);
        $subjects = array_merge(self::SUBJECTS, $extra);

        $grades = array_unique(array_merge(PerformanceMps::GRADE_LEVELS, ...array_map('array_keys', array_values($sections))));
        usort($grades, static fn ($a, $b) => (int) preg_replace('/\D/', '', $a) <=> (int) preg_replace('/\D/', '', $b));

        // Breakdown rows per subject, sorted by grade then section, with the teacher(s) and average.
        $teachers  = $this->teachersByCell($year, $term);
        $breakdown = [];
        foreach ($sections as $subject => $byGrade) {
            foreach ($grades as $grade) {
                $bySection = $byGrade[$grade] ?? [];
                ksort($bySection, SORT_NATURAL | SORT_FLAG_CASE);
                foreach ($bySection as $label => $r) {
                    $values = array_filter([$r['s1'], $r['s2'], $r['exam']], static fn ($v) => $v !== null);
                    $breakdown[$subject][] = $r + [
                        'grade'   => $grade,
                        'avg'     => $values ? round(array_sum($values) / count($values), 2) : null,
                        'teacher' => implode(', ', $teachers[$grade . '|' . $subject . '|' . $label] ?? []),
                    ];
                }
            }
        }

        return view('pages/admin/mps_summary', [
            'pageTitle' => 'MPS Summary',
            'year'      => $year,
            'term'      => $term,
            'years'     => $years,
            'terms'     => PerformanceMps::TERM_OPTIONS,
            'periods'   => PerformanceMps::PERIOD_MAP,
            'grades'    => $grades,
            'subjects'  => $subjects,
            'grid'      => $grid,
            'breakdown' => $breakdown,
        ]);
    }

    /** @return list<string> school years that have MPS scores, newest first */
    private function yearsWithScores(): array
    {
        $years = array_column((new MpsTestScoreModel())->select('school_year')->distinct()->findAll(), 'school_year');
        rsort($years);

        return array_values($years);
    }

    /**
     * Who handles each class, from every teacher's subject load for the term
     * (the same cells they enter MPS for).
     *
     * @return array<string,list<string>> "Grade X|Subject|SectionLabel" => teacher names
     */
    private function teachersByCell(string $year, int $term): array
    {
        $mps = new PerformanceMps();
        $map = [];
        foreach ((new TeacherModel())->select('id, name')->orderBy('name')->findAll() as $t) {
            foreach (array_keys($mps->handledCellsForTeacher((int) $t['id'], $year, $term)) as $cellKey) {
                $map[$cellKey][] = $t['name'];
            }
        }

        return $map;
    }
}
