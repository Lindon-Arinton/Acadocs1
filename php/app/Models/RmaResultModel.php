<?php

namespace App\Models;

use App\Controllers\Teacher\PerformanceMps;
use CodeIgniter\Model;

/**
 * Rapid Mathematics Assessment (RMA) results, Grades 7–10 — laid out like
 * DepEd's "School Rapid Mathematics Assessment Results" sheet: per section
 * and sex, the learners assessed by proficiency level, content and cognitive
 * domain scores, and the mean score.
 */
class RmaResultModel extends Model
{
    public const PERIODS = ['BoSY' => 'Beginning of SY (Pre-test)', 'MoSY' => 'Middle of SY', 'EoSY' => 'End of SY (Post-test)'];
    public const SEXES   = ['Male', 'Female'];

    /** Number of learners assessed, by proficiency level. */
    public const LEVELS = [
        'lvl_not_proficient'    => ['Emerging', 'Not Proficient'],
        'lvl_low_proficient'    => ['Emerging', 'Low Proficient'],
        'lvl_nearly_proficient' => ['Developing', 'Nearly Proficient'],
        'lvl_proficient'        => ['Transitioning', 'Proficient'],
        'lvl_highly_proficient' => ['At Grade Level', 'Highly Proficient'],
    ];
    public const CONTENT = [
        'cd_number_algebra'       => 'Number and Algebra',
        'cd_measurement_geometry' => 'Measurement and Geometry',
        'cd_data_probability'     => 'Data and Probability',
    ];
    public const COGNITIVE = [
        'cg_knowing'      => 'Knowing',
        'cg_interpreting' => 'Interpreting',
        'cg_applying'     => 'Applying',
        'cg_reasoning'    => 'Reasoning',
    ];
    public const COUNT_FIELDS = ['enrolment', 'assessed', 'lvl_not_proficient', 'lvl_low_proficient', 'lvl_nearly_proficient', 'lvl_proficient', 'lvl_highly_proficient'];
    public const SCORE_FIELDS = ['cd_number_algebra', 'cd_measurement_geometry', 'cd_data_probability', 'cg_knowing', 'cg_interpreting', 'cg_applying', 'cg_reasoning', 'mean_score'];

    protected $table         = 'rma_results';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'school_year', 'period', 'grade_level', 'section', 'sex',
        'enrolment', 'assessed', 'lvl_not_proficient', 'lvl_low_proficient', 'lvl_nearly_proficient', 'lvl_proficient', 'lvl_highly_proficient',
        'cd_number_algebra', 'cd_measurement_geometry', 'cd_data_probability',
        'cg_knowing', 'cg_interpreting', 'cg_applying', 'cg_reasoning', 'mean_score',
        'entered_by', 'updated_at',
    ];

    /**
     * The school-wide RMA table for a school year: every Math section's Male /
     * Female rows, for the given assessment period — or, when none is given,
     * the latest one that has data. Used by the principal's RMA page and dashboard.
     */
    public static function schoolTable(string $year, ?string $period = null): array
    {
        $model  = new self();
        $counts = array_column($model->select('period, COUNT(*) AS n')->where('school_year', $year)->groupBy('period')->findAll(), 'n', 'period');

        if (! isset(self::PERIODS[(string) $period])) {
            $period = 'BoSY';
            foreach (array_reverse(array_keys(self::PERIODS)) as $p) {
                if (! empty($counts[$p])) {
                    $period = $p;
                    break;
                }
            }
        }

        return [
            'year'     => $year,
            'period'   => $period,
            'counts'   => $counts,
            'sections' => self::mathSections($year, $period),
            'rows'     => (new self())->keyed($year, $period),
        ];
    }

    /**
     * Figures for the principal's dashboard charts, per grade level and for
     * the whole school: learners by proficiency level, enrolled / assessed,
     * and the mean score (weighted by learners assessed).
     */
    public static function summary(string $year, ?string $period = null): array
    {
        $table  = self::schoolTable($year, $period);
        $blank  = static fn () => ['enrolment' => 0, 'assessed' => 0, 'levels' => array_fill_keys(array_keys(self::LEVELS), 0), 'meanSum' => 0.0, 'meanWeight' => 0];
        $grades = [];
        foreach (PerformanceMps::GRADE_LEVELS as $g) {
            $grades[$g] = $blank();
        }
        $school   = $blank();
        $reported = [];

        foreach ($table['rows'] as $r) {
            if (! isset($grades[$r['grade_level']])) {
                continue;
            }
            $reported[$r['grade_level'] . '|' . strtolower($r['section'])] = true;
            foreach ([&$grades[$r['grade_level']], &$school] as &$bucket) {
                $bucket['enrolment'] += (int) $r['enrolment'];
                $bucket['assessed']  += (int) $r['assessed'];
                foreach (array_keys(self::LEVELS) as $f) {
                    $bucket['levels'][$f] += (int) $r[$f];
                }
                if ($r['mean_score'] !== null && (int) $r['assessed'] > 0) {
                    $bucket['meanSum']    += (float) $r['mean_score'] * (int) $r['assessed'];
                    $bucket['meanWeight'] += (int) $r['assessed'];
                }
            }
            unset($bucket);
        }

        $finish = static function (array $b): array {
            $b['mean']    = $b['meanWeight'] > 0 ? round($b['meanSum'] / $b['meanWeight'], 2) : null;
            $b['leveled'] = array_sum($b['levels']);
            // Transitioning (Proficient) + At Grade Level (Highly Proficient).
            $b['proficientPct'] = $b['leveled'] > 0 ? round(($b['levels']['lvl_proficient'] + $b['levels']['lvl_highly_proficient']) / $b['leveled'] * 100, 1) : null;
            unset($b['meanSum'], $b['meanWeight']);

            return $b;
        };

        return [
            'year'             => $table['year'],
            'period'           => $table['period'],
            'counts'           => $table['counts'],
            'grades'           => array_map($finish, $grades),
            'school'           => $finish($school),
            'sectionsReported' => count($reported),
            'sectionsTotal'    => count($table['sections']),
        ];
    }

    /**
     * Whether a subject (as typed in a subject load) is Mathematics: digits,
     * dots and spaces are ignored, so "MATH", "Math 9", "Mathematics 8" and
     * "MATHEMATICS (4)" all count. Same rule for the RMA menu link, the
     * teacher's RMA page and the school table.
     */
    public static function isMathSubject(?string $subject): bool
    {
        $code = strtoupper((string) preg_replace('/\([^)]*\)|[^A-Za-z]/', '', (string) $subject));

        return in_array($code, ['MATH', 'MATHEMATICS'], true);
    }

    /** Whether the teacher (teachers.id) has a Math subject in any subject load. */
    public static function teacherHasMath(int $teacherId): bool
    {
        foreach (db_connect()->query('SELECT DISTINCT subject FROM teacher_subjects WHERE teacher_id = ?', [$teacherId])->getResultArray() as $r) {
            if (self::isMathSubject($r['subject'])) {
                return true;
            }
        }

        return false;
    }

    /** Whether a teacher account handles Math — shows "RMA (Math)" in their menu. */
    public static function userHasMath(array $user): bool
    {
        $teacher = db_connect()->query('SELECT id FROM teachers WHERE user_id = ? OR email = ? LIMIT 1', [(int) ($user['id'] ?? 0), (string) ($user['email'] ?? '')])->getRowArray();

        return $teacher !== null && self::teacherHasMath((int) $teacher['id']);
    }

    /**
     * Call after a teacher's subject load changes (principal's User form, an
     * approved subject request): if they now handle Math and didn't before,
     * tell them RMA (Math) is in their menu.
     */
    public static function announceIfNewMath(int $teacherId, bool $hadMath): void
    {
        if ($hadMath || ! self::teacherHasMath($teacherId)) {
            return;
        }
        $teacher = db_connect()->query('SELECT user_id FROM teachers WHERE id = ?', [$teacherId])->getRowArray();
        if (empty($teacher['user_id'])) {
            return;
        }
        (new NotificationModel())->upsertGrouped(
            (int) $teacher['user_id'],
            'rma_access',
            $teacherId,
            'rma',
            'RMA (Math) is now in your menu',
            'You handle Mathematics — enter your sections\' Rapid Mathematics Assessment results there.',
            base_url('rma')
        );
    }

    /** Rows for a school year + period keyed "Grade X|Section|Sex". */
    public function keyed(string $year, string $period): array
    {
        $out = [];
        foreach ($this->where('school_year', $year)->where('period', $period)->findAll() as $r) {
            $out[$r['grade_level'] . '|' . $r['section'] . '|' . $r['sex']] = $r;
        }

        return $out;
    }

    /**
     * Every Grade 7–10 section that has a Math class (any term's subject load,
     * plus any section that already has RMA data), sorted by grade then name.
     *
     * @return list<array{grade:string,section:string}>
     */
    public static function mathSections(string $year, string $period): array
    {
        $db   = db_connect();
        $math = array_filter(
            $db->query("SELECT DISTINCT subject, grade_level, section FROM teacher_subjects
                         WHERE section IS NOT NULL AND section <> '' AND grade_level IS NOT NULL")->getResultArray(),
            static fn ($r) => self::isMathSubject($r['subject'])
        );
        $entered = $db->query('SELECT DISTINCT grade_level, section FROM rma_results WHERE school_year = ? AND period = ?', [$year, $period])->getResultArray();

        return self::sortSections(array_map(static fn ($r) => ['grade' => $r['grade_level'], 'section' => $r['section']], array_merge($math, $entered)));
    }

    /** @param list<array{grade:string,section:string}> $sections */
    public static function sortSections(array $sections): array
    {
        $byKey = [];
        foreach ($sections as $s) {
            if (in_array($s['grade'], PerformanceMps::GRADE_LEVELS, true)) {
                $byKey[strtolower($s['grade'] . '|' . $s['section'])] = $s;
            }
        }
        $list = array_values($byKey);
        usort($list, static fn ($a, $b) => [(int) preg_replace('/\D/', '', $a['grade']), strtolower($a['section'])]
            <=> [(int) preg_replace('/\D/', '', $b['grade']), strtolower($b['section'])]);

        return $list;
    }

    public static function contentTotal(?array $r): ?float
    {
        return self::sum($r, array_keys(self::CONTENT));
    }

    public static function cognitiveTotal(?array $r): ?float
    {
        return self::sum($r, array_keys(self::COGNITIVE));
    }

    private static function sum(?array $r, array $fields): ?float
    {
        if (! $r) {
            return null;
        }
        $values = array_filter(array_map(static fn ($f) => $r[$f] ?? null, $fields), static fn ($v) => $v !== null && $v !== '');

        return $values === [] ? null : round(array_sum(array_map('floatval', $values)), 2);
    }

    /**
     * The sheet's Status column: "Data Validated" once the counts add up —
     * learners assessed ≤ enrolment, and the five proficiency levels add up
     * to the learners assessed — and the mean score is in.
     *
     * @return array{0:string,1:string} [label, tone: ok|warn|none]
     */
    public static function status(?array $r): array
    {
        if (! $r) {
            return ['No data', 'none'];
        }
        foreach (['enrolment', 'assessed', 'mean_score'] as $f) {
            if ($r[$f] === null || $r[$f] === '') {
                return ['Incomplete', 'warn'];
            }
        }
        if ((int) $r['assessed'] > (int) $r['enrolment']) {
            return ['Assessed > enrolment', 'warn'];
        }
        $levels = array_sum(array_map(static fn ($f) => (int) ($r[$f] ?? 0), array_keys(self::LEVELS)));
        if ($levels !== (int) $r['assessed']) {
            return ['Levels ≠ assessed', 'warn'];
        }

        return ['Data Validated', 'ok'];
    }
}
