<?php

namespace App\Models;

use App\Controllers\Teacher\PerformanceMps;
use CodeIgniter\Model;

/**
 * Reading Program results (Phil-IRI Filipino / English, Group Screening Test,
 * Project TALA): one row per school year + period + language + section.
 *
 * `counts` (JSON) holds the Male / Female count of every level of every table:
 *   {"wr": {"independent": {"m": 5, "f": 6}, ...}, "rc": ..., "rp": ..., "gst": ..., "tala": ...}
 * Totals and % are never stored — they're worked out from the counts.
 */
class ReadingResultModel extends Model
{
    public const LANGUAGES = ['Filipino', 'English'];
    public const PERIODS   = ['Beginning' => 'Beginning (Pre-test)', 'End' => 'Year End (Post-test)'];
    public const STATUSES  = ['Draft', 'Submitted', 'Approved', 'Returned'];

    /** The four Phil-IRI levels, best first (labels per language). */
    public const LEVELS = [
        'independent'   => ['Filipino' => 'Malaya',        'English' => 'Independent'],
        'instructional' => ['Filipino' => 'Instruksyunal', 'English' => 'Instructional'],
        'frustration'   => ['Filipino' => 'Kabiguan',      'English' => 'Frustration'],
        'nonreader'     => ['Filipino' => 'Di-makabasa',   'English' => 'Non-Reader'],
    ];
    public const GST_BANDS = [
        'down3'       => ['Filipino' => '3 Antas Pababa', 'English' => '3 Level Down', 'range' => '0–15'],
        'down2'       => ['Filipino' => '2 Antas Pababa', 'English' => '2 Level Down', 'range' => '16–27'],
        'independent' => ['Filipino' => 'Malaya',         'English' => 'Independent',  'range' => '28–40'],
    ];
    /** Project TALA (English only): S = speed, C = comprehension. */
    public const TALA = ['spcp' => 'S+ C+', 'spcm' => 'S+ C−', 'smcp' => 'S− C+', 'smcm' => 'S− C−'];

    protected $table         = 'reading_results';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'school_year', 'period', 'language', 'grade_level', 'section',
        'enrolled_m', 'enrolled_f', 'tested_m', 'tested_f', 'counts',
        'status', 'review_note', 'updated_by', 'submitted_at', 'reviewed_by', 'reviewed_at', 'updated_at',
    ];

    /**
     * The tables encoded for a language: key => [title, subtitle, level keys + labels].
     *
     * @return array<string,array{title:string,sub:string,levels:array<string,string>}>
     */
    public static function tables(string $language): array
    {
        $fil    = $language === 'Filipino';
        $levels = array_map(static fn ($l) => $l[$language], self::LEVELS);
        $tables = [
            'wr' => ['title' => $fil ? 'Antas ng Pagkilala ng mga Salita' : 'Word Recognition', 'sub' => $fil ? 'Word Recognition' : 'Antas ng Pagkilala ng mga Salita', 'levels' => $levels],
            'rc' => ['title' => $fil ? 'Antas ng Pag-unawa ng Salita' : 'Reading Comprehension', 'sub' => $fil ? 'Reading Comprehension' : 'Antas ng Pag-unawa', 'levels' => $levels],
            'rp' => ['title' => $fil ? 'Antas ng Kakayahan sa Pagbasa' : 'Reading Profile', 'sub' => $fil ? 'Reading Profile' : 'Antas ng Kakayahan sa Pagbasa', 'levels' => $levels],
            'gst' => [
                'title'  => $fil ? 'Group Screening Test (GST)' : 'GST Score Band',
                'sub'    => $fil ? 'Pangkatang Pagtataya' : 'Group Screening Test',
                'levels' => array_map(static fn ($b) => $b[$language] . ' · ' . $b['range'], self::GST_BANDS),
            ],
        ];
        if (! $fil) {
            $tables['tala'] = ['title' => 'Project TALA', 'sub' => 'English only', 'levels' => self::TALA];
        }

        return $tables;
    }

    public static function decodeCounts(?string $json): array
    {
        $c = json_decode((string) $json, true);

        return is_array($c) ? $c : [];
    }

    /** Male / Female / total of one table. */
    public static function tableTotals(array $counts, string $table, array $levelKeys): array
    {
        $m = $f = 0;
        foreach ($levelKeys as $k) {
            $m += (int) ($counts[$table][$k]['m'] ?? 0);
            $f += (int) ($counts[$table][$k]['f'] ?? 0);
        }

        return ['m' => $m, 'f' => $f, 'total' => $m + $f];
    }

    /**
     * What stops a section from being submitted (empty = ready). Same rules as
     * the live checks on the encoding page.
     *
     * @return list<string>
     */
    public static function problems(array $row): array
    {
        $fil = ($row['language'] ?? '') === 'Filipino';
        $p   = [];
        foreach (['enrolled_m', 'enrolled_f', 'tested_m', 'tested_f'] as $f) {
            if ($row[$f] === null || $row[$f] === '') {
                $p[] = $fil ? 'Kumpletuhin ang bilang ng naka-enroll at kumuha.' : 'Fill in the enrolled and tested counts.';
                break;
            }
        }
        if ($p) {
            return $p;
        }
        if ((int) $row['tested_m'] > (int) $row['enrolled_m'] || (int) $row['tested_f'] > (int) $row['enrolled_f']) {
            $p[] = $fil ? 'Ang kumuha ay hindi maaaring higit sa naka-enroll.' : 'Tested cannot be more than enrolled.';
        }
        if ((int) $row['tested_m'] + (int) $row['tested_f'] === 0) {
            $p[] = $fil ? 'Walang kumuha ng pagtataya.' : 'No learners were tested.';
        }
        $counts = self::decodeCounts($row['counts'] ?? null);
        foreach (self::tables($row['language']) as $key => $t) {
            $tot = self::tableTotals($counts, $key, array_keys($t['levels']));
            if ($tot['m'] !== (int) $row['tested_m'] || $tot['f'] !== (int) $row['tested_f']) {
                $p[] = $fil
                    ? 'Ang ' . $t['title'] . ' ay hindi tugma sa bilang ng kumuha.'
                    : $t['title'] . ' does not add up to the number tested.';
            }
        }

        return $p;
    }

    /** Every Grade 7–10 section in the school (from the teachers' subject loads), by grade. */
    public static function sectionsByGrade(): array
    {
        $out = array_fill_keys(PerformanceMps::GRADE_LEVELS, []);
        foreach (db_connect()->query("SELECT DISTINCT grade_level, section FROM teacher_subjects WHERE section IS NOT NULL AND section <> '' ORDER BY section")->getResultArray() as $r) {
            if (isset($out[$r['grade_level']]) && ! in_array($r['section'], $out[$r['grade_level']], true)) {
                $out[$r['grade_level']][] = $r['section'];
            }
        }

        return $out;
    }

    /** Rows for a school year + period + language keyed "Grade X|Section". */
    public function keyed(string $year, string $period, string $language): array
    {
        $out = [];
        foreach ($this->where('school_year', $year)->where('period', $period)->where('language', $language)->findAll() as $r) {
            $out[$r['grade_level'] . '|' . $r['section']] = $r;
        }

        return $out;
    }

    /** Languages a user was assigned to encode. */
    public static function assignedLanguages(int $userId): array
    {
        return array_column(db_connect()->query('SELECT language FROM reading_assignments WHERE user_id = ? ORDER BY language', [$userId])->getResultArray(), 'language');
    }

    /**
     * Per-grade + school totals for the principal's charts and summary tables
     * (only rows that were submitted or approved count, so drafts don't skew it).
     *
     * @return array{grades:array,school:array}
     */
    public static function summary(string $year, string $period, string $language, bool $includeDrafts = false): array
    {
        $tables = self::tables($language);
        $blank  = static function () use ($tables) {
            $b = ['enrolled_m' => 0, 'enrolled_f' => 0, 'tested_m' => 0, 'tested_f' => 0, 'sections' => 0, 'tables' => []];
            foreach ($tables as $key => $t) {
                foreach (array_keys($t['levels']) as $lv) {
                    $b['tables'][$key][$lv] = ['m' => 0, 'f' => 0];
                }
            }

            return $b;
        };
        $grades = [];
        foreach (PerformanceMps::GRADE_LEVELS as $g) {
            $grades[$g] = $blank();
        }
        $school = $blank();

        foreach ((new self())->keyed($year, $period, $language) as $r) {
            if (! isset($grades[$r['grade_level']]) || (! $includeDrafts && ! in_array($r['status'], ['Submitted', 'Approved'], true))) {
                continue;
            }
            $counts = self::decodeCounts($r['counts']);
            foreach ([&$grades[$r['grade_level']], &$school] as &$b) {
                foreach (['enrolled_m', 'enrolled_f', 'tested_m', 'tested_f'] as $f) {
                    $b[$f] += (int) $r[$f];
                }
                $b['sections']++;
                foreach ($tables as $key => $t) {
                    foreach (array_keys($t['levels']) as $lv) {
                        $b['tables'][$key][$lv]['m'] += (int) ($counts[$key][$lv]['m'] ?? 0);
                        $b['tables'][$key][$lv]['f'] += (int) ($counts[$key][$lv]['f'] ?? 0);
                    }
                }
            }
            unset($b);
        }

        return ['grades' => $grades, 'school' => $school];
    }

    /** % of a count against the learners tested (one decimal), or null when nobody was tested. */
    public static function pct(int $count, int $tested): ?float
    {
        return $tested > 0 ? round($count / $tested * 100, 1) : null;
    }

    /**
     * School year / period from the query string, else the school year in
     * progress (Term 3 → Year End, otherwise Beginning).
     *
     * @return array{0:string,1:string,2:list<string>} [year, period, year options]
     */
    public static function yearPeriod(?string $year, ?string $period): array
    {
        $withData = array_column(db_connect()->query('SELECT DISTINCT school_year FROM reading_results ORDER BY school_year DESC')->getResultArray(), 'school_year');
        $years    = array_values(array_unique(array_merge($withData, PerformanceMps::YEAR_OPTIONS)));
        rsort($years);

        $today = (new \App\Libraries\SchoolCalendar())->termOn();
        $year  = trim((string) $year);
        if (! in_array($year, $years, true)) {
            $year = $today['schoolYear'] ?? $years[0];
            if (! in_array($year, $years, true)) {
                array_unshift($years, $year);
            }
        }
        if (! isset(self::PERIODS[(string) $period])) {
            $period = (int) ($today['term'] ?? 1) >= 3 ? 'End' : 'Beginning';
        }

        return [$year, $period, $years];
    }

    /** All sections by grade, plus any that only appear in saved results. */
    public static function sectionsWithRows(array $rows): array
    {
        $sections = self::sectionsByGrade();
        foreach ($rows as $r) {
            if (isset($sections[$r['grade_level']]) && ! in_array($r['section'], $sections[$r['grade_level']], true)) {
                $sections[$r['grade_level']][] = $r['section'];
                sort($sections[$r['grade_level']]);
            }
        }

        return $sections;
    }

    /** [label, tone] of a section's row for the status pills (tone → .rd-status-*). */
    public static function statusOf(?array $row): array
    {
        return match ($row['status'] ?? null) {
            'Draft'     => ['Draft', 'draft'],
            'Submitted' => ['Submitted', 'submitted'],
            'Approved'  => ['Approved', 'approved'],
            'Returned'  => ['Returned', 'returned'],
            default     => ['Not started', 'none'],
        };
    }

    /** Active principals (they get the "submitted for review" alerts). */
    public static function principalIds(): array
    {
        return array_map('intval', array_column(db_connect()->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1")->getResultArray(), 'id'));
    }
}
