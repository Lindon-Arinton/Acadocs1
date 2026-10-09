<?php

namespace App\Libraries;

use App\Models\MpsTestScoreModel;
use App\Models\PerformanceByLevelModel;
use App\Models\PerformanceBySubjectModel;

/**
 * Saves raw per-test-period MPS scores (Summative Test 1/2, Term Examination)
 * and rolls them up into `performance_by_subject` / `performance_by_level`,
 * mirroring the school's existing MPS tracking spreadsheet.
 */
class MpsCalculator
{
    private const PLACEHOLDER_INSTRUCTOR = '—';

    /** Instructor values that don't name a real person (placeholder / seeded data). */
    public const UNKNOWN_INSTRUCTORS = ['', self::PLACEHOLDER_INSTRUCTOR, 'Subject Teacher'];

    private MpsTestScoreModel $scores;
    private PerformanceBySubjectModel $bySubject;
    private PerformanceByLevelModel $byLevel;

    public function __construct()
    {
        $this->scores    = new MpsTestScoreModel();
        $this->bySubject = new PerformanceBySubjectModel();
        $this->byLevel   = new PerformanceByLevelModel();
    }

    /**
     * @param array<string,array<string,array<string,mixed>>> $scoresByPeriod
     *   [testPeriodLabel => [gradeLevel => [subject => mps]]] — one already-blended
     *   value per grade+subject, e.g. from the Excel importer's grade-level grid,
     *   which doesn't break scores out by section. Saved with section = null.
     * @param string|null $instructor name of the teacher saving these scores, recorded
     *   against every grade+subject they touched (see assignInstructor()).
     */
    public function saveScores(string $schoolYear, int $term, array $scoresByPeriod, ?string $instructor = null): void
    {
        $touched = [];
        foreach ($scoresByPeriod as $testPeriod => $byGrade) {
            foreach ($byGrade as $gradeLevel => $bySubject) {
                foreach ($bySubject as $subject => $value) {
                    $value = trim((string) $value);
                    if ($value === '') {
                        continue;
                    }

                    $this->upsertScore($schoolYear, $term, $gradeLevel, $subject, null, $testPeriod, (float) $value);
                    $touched[$gradeLevel . '|' . $subject] = [$gradeLevel, $subject];
                }
            }
        }

        $this->recompute($schoolYear, $term);
        $this->assignInstructor($schoolYear, $term, $touched, $instructor);
    }

    /**
     * @param array<int,array{period:string,grade:string,subject:string,section:?string,mps:float}> $entries
     *   Per-section scores from the manual MPS entry form (see PerformanceMps::handledCells()).
     *   $entry['section'] is null for a teacher's legacy grade+subject-only rows
     *   (no real section name on record), same as an imported blended value.
     */
    public function saveSectionScores(string $schoolYear, int $term, array $entries, ?string $instructor = null): void
    {
        $touched = [];
        foreach ($entries as $entry) {
            $this->upsertScore($schoolYear, $term, $entry['grade'], $entry['subject'], $entry['section'], $entry['period'], $entry['mps']);
            $touched[$entry['grade'] . '|' . $entry['subject']] = [$entry['grade'], $entry['subject']];
        }

        $this->recompute($schoolYear, $term);
        $this->assignInstructor($schoolYear, $term, $touched, $instructor);
    }

    /**
     * Records who handles each grade+subject the teacher just saved scores
     * for. Several teachers can share one grade+subject (different sections),
     * so a new name is appended ("A, B") rather than replacing the existing one;
     * a placeholder is simply overwritten.
     *
     * @param array<string,array{0:string,1:string}> $touched [grade, subject] pairs
     */
    private function assignInstructor(string $schoolYear, int $term, array $touched, ?string $instructor): void
    {
        $instructor = trim((string) $instructor);
        if ($instructor === '') {
            return;
        }

        foreach ($touched as [$gradeLevel, $subject]) {
            $row = $this->bySubject
                ->where('school_year', $schoolYear)->where('term', $term)
                ->where('subject', $subject)->where('grade_level', $gradeLevel)
                ->first();
            if ($row === null) {
                continue;
            }

            $current = trim((string) $row['instructor']);
            if (in_array($current, self::UNKNOWN_INSTRUCTORS, true)) {
                $names = [$instructor];
            } else {
                $names = array_map('trim', explode(',', $current));
                if (in_array($instructor, $names, true)) {
                    continue;
                }
                $names[] = $instructor;
            }

            // Column is VARCHAR(100).
            $this->bySubject->update($row['id'], ['instructor' => mb_substr(implode(', ', $names), 0, 100)]);
        }
    }

    private function upsertScore(string $schoolYear, int $term, string $gradeLevel, string $subject, ?string $section, string $testPeriod, float $mps): void
    {
        $existing = $this->scores
            ->where('school_year', $schoolYear)
            ->where('term', $term)
            ->where('grade_level', $gradeLevel)
            ->where('subject', $subject)
            ->where('section', $section)
            ->where('test_period', $testPeriod)
            ->first();

        $data = [
            'school_year' => $schoolYear,
            'term'        => $term,
            'grade_level' => $gradeLevel,
            'subject'     => $subject,
            'section'     => $section,
            'test_period' => $testPeriod,
            'mps'         => $mps,
        ];

        if ($existing !== null) {
            $this->scores->update($existing['id'], $data);
        } else {
            $this->scores->insert($data);
        }
    }

    private function recompute(string $schoolYear, int $term): void
    {
        $rows = $this->scores->forYearTerm($schoolYear, $term);

        $bySubjectGrade = [];
        foreach ($rows as $row) {
            $key = $row['grade_level'] . '|' . $row['subject'];
            $bySubjectGrade[$key]['grade_level'] ??= $row['grade_level'];
            $bySubjectGrade[$key]['subject'] ??= $row['subject'];
            $bySubjectGrade[$key]['values'][] = (float) $row['mps'];
        }

        $levelSubjectMps = [];

        foreach ($bySubjectGrade as $entry) {
            $mps = array_sum($entry['values']) / count($entry['values']);
            $this->upsertPerformanceBySubject($schoolYear, $term, $entry['subject'], $entry['grade_level'], $mps);
            $levelSubjectMps[$entry['grade_level']][] = $mps;
        }

        foreach ($levelSubjectMps as $gradeLevel => $subjectMpsList) {
            $levelMps = array_sum($subjectMpsList) / count($subjectMpsList);
            $this->upsertPerformanceByLevel($schoolYear, $term, $gradeLevel, $levelMps);
        }
    }

    private function upsertPerformanceBySubject(string $schoolYear, int $term, string $subject, string $gradeLevel, float $mps): void
    {
        $existing = $this->bySubject
            ->where('school_year', $schoolYear)
            ->where('term', $term)
            ->where('subject', $subject)
            ->where('grade_level', $gradeLevel)
            ->first();

        $data = [
            'school_year' => $schoolYear,
            'term'        => $term,
            'subject'     => $subject,
            'grade_level' => $gradeLevel,
            'instructor'  => $existing['instructor'] ?? self::PLACEHOLDER_INSTRUCTOR,
            'mps'         => round($mps, 2),
        ];

        if ($existing !== null) {
            $this->bySubject->update($existing['id'], $data);
        } else {
            $this->bySubject->insert($data);
        }
    }

    private function upsertPerformanceByLevel(string $schoolYear, int $term, string $gradeLevel, float $mps): void
    {
        $existing = $this->byLevel
            ->where('school_year', $schoolYear)
            ->where('term', $term)
            ->where('grade_level', $gradeLevel)
            ->first();

        $data = [
            'school_year' => $schoolYear,
            'term'        => $term,
            'grade_level' => $gradeLevel,
            'mps'         => round($mps, 2),
        ];

        if ($existing !== null) {
            $this->byLevel->update($existing['id'], $data);
        } else {
            $this->byLevel->insert($data);
        }
    }
}
