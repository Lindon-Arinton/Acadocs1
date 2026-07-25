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
     *   [testPeriodLabel => [gradeLevel => [subject => mps]]]
     */
    public function saveScores(string $schoolYear, int $term, array $scoresByPeriod): void
    {
        foreach ($scoresByPeriod as $testPeriod => $byGrade) {
            foreach ($byGrade as $gradeLevel => $bySubject) {
                foreach ($bySubject as $subject => $value) {
                    $value = trim((string) $value);
                    if ($value === '') {
                        continue;
                    }

                    $this->upsertScore($schoolYear, $term, $gradeLevel, $subject, $testPeriod, (float) $value);
                }
            }
        }

        $this->recompute($schoolYear, $term);
    }

    private function upsertScore(string $schoolYear, int $term, string $gradeLevel, string $subject, string $testPeriod, float $mps): void
    {
        $existing = $this->scores
            ->where('school_year', $schoolYear)
            ->where('term', $term)
            ->where('grade_level', $gradeLevel)
            ->where('subject', $subject)
            ->where('test_period', $testPeriod)
            ->first();

        $data = [
            'school_year' => $schoolYear,
            'term'        => $term,
            'grade_level' => $gradeLevel,
            'subject'     => $subject,
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
