<?php

namespace App\Libraries;

use App\Controllers\Teacher\PerformanceMps;
use App\Models\AcademicBreakModel;
use App\Models\EnrollmentByLevelModel;
use App\Models\HolidayModel;
use App\Models\MpsTestScoreModel;
use App\Models\PerformanceBySubjectModel;
use App\Models\TaskAssigneeModel;
use App\Models\TaskModel;
use App\Models\TaskSubmissionModel;
use App\Models\TimeRecordModel;
use App\Models\UserModel;

/**
 * Data for the end-of-term / end-of-year report pack (Admin\TermPack):
 * enrollment, staff attendance, MPS by learning area and task compliance
 * for one term (or the whole school year) of the DepEd calendar.
 */
class TermPack
{
    /** Terms of a configured school year: [term => [start, end]] (empty when not on the calendar). */
    public static function termsOf(string $year): array
    {
        $info = config('SchoolCalendar')->years[$year] ?? null;

        return $info ? array_map(static fn (array $t) => [$t['start'], $t['end']], $info['terms']) : [];
    }

    /**
     * @param string $term '1'..'3', or 'year' for the whole school year
     * @return array<string,mixed>|null null when the year/term isn't on the school calendar
     */
    public function build(string $year, string $term): ?array
    {
        $terms = self::termsOf($year);
        if ($terms === [] || ($term !== 'year' && ! isset($terms[(int) $term]))) {
            return null;
        }

        [$start, $end] = $term === 'year'
            ? [reset($terms)[0], end($terms)[1]]
            : $terms[(int) $term];
        $asOf = min($end, date('Y-m-d')); // a pack opened mid-term covers up to today

        return [
            'year'       => $year,
            'term'       => $term,
            'label'      => $term === 'year' ? 'End-of-Year Report' : 'End-of-Term ' . $term . ' Report',
            'start'      => $start,
            'end'        => $end,
            'asOf'       => $asOf,
            'complete'   => $asOf === $end,
            'enrollment' => $this->enrollment($year, $end),
            'attendance' => $this->attendance($start, $asOf),
            'mps'        => $term === 'year' ? $this->mpsForYear($year, array_keys($terms)) : $this->mpsForTerm($year, (int) $term),
            'classes'    => $this->classesBelowMastery($year, $term === 'year' ? array_keys($terms) : [(int) $term]),
            'tasks'      => $this->tasks($start, $end, $asOf),
        ];
    }

    /** The latest enrollment snapshot taken by the end of the period. */
    private function enrollment(string $year, string $end): array
    {
        $model  = new EnrollmentByLevelModel();
        $months = array_filter($model->monthsFor($year), static fn (string $m) => $m <= substr($end, 0, 7));
        $month  = $months !== [] ? end($months) : null;
        $rows   = $model->forYear($year, $month);

        return [
            'month' => $month,
            'rows'  => $rows,
            'total' => array_sum(array_map(static fn ($r) => (int) $r['students'], $rows)),
        ];
    }

    /** Staff attendance between two dates, per person and in total. */
    private function attendance(string $start, string $end): array
    {
        $records = (new TimeRecordModel())
            ->where('date >=', $start)->where('date <=', $end)
            ->where('employee_name NOT LIKE', 'Unmapped (%')
            ->findAll();

        $totals = array_fill_keys(TimeRecordModel::STATUSES, 0);
        $people = [];
        foreach ($records as $r) {
            $totals[$r['status']] = ($totals[$r['status']] ?? 0) + 1;
            $p                    = &$people[$r['employee_id']];
            $p ??= ['name' => $r['employee_name'], 'counts' => array_fill_keys(TimeRecordModel::STATUSES, 0)];
            $p['counts'][$r['status']]++;
            unset($p);
        }

        foreach ($people as &$p) {
            $c         = $p['counts'];
            $attended  = $c['Present'] + $c['Late'];
            $p['rate'] = $attended + $c['Absent'] > 0 ? round($attended / ($attended + $c['Absent']) * 100, 1) : null;
        }
        unset($p);
        uasort($people, static fn ($a, $b) => [$b['counts']['Absent'], $b['counts']['Late'], $a['name']] <=> [$a['counts']['Absent'], $a['counts']['Late'], $b['name']]);

        // School days in the period: weekdays that aren't holidays or academic breaks.
        $holidays   = new HolidayModel();
        $breaks     = (new AcademicBreakModel())->findAll();
        $schoolDays = 0;
        for ($d = $start; $d <= $end; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            if ((int) date('N', strtotime($d)) >= 6 || $holidays->labelFor($d) !== null) {
                continue;
            }
            foreach ($breaks as $b) {
                if ($d >= $b['start_date'] && $d <= $b['end_date']) {
                    continue 2;
                }
            }
            $schoolDays++;
        }

        $attended = $totals['Present'] + $totals['Late'];

        return [
            'totals'     => $totals,
            'people'     => array_values($people),
            'schoolDays' => $schoolDays,
            'rate'       => $attended + $totals['Absent'] > 0 ? round($attended / ($attended + $totals['Absent']) * 100, 1) : null,
        ];
    }

    /** Learning areas for one term: MPS per grade level and the average across them. */
    private function mpsForTerm(string $year, int $term): array
    {
        $rows      = (new PerformanceBySubjectModel())->where('school_year', $year)->where('term', $term)->findAll();
        $grades    = [];
        $bySubject = [];
        foreach ($rows as $r) {
            $grades[$r['grade_level']]                         = true;
            $bySubject[$r['subject']][$r['grade_level']] = (float) $r['mps'];
        }
        $grades = array_keys($grades);
        usort($grades, static fn ($a, $b) => (int) preg_replace('/\D/', '', $a) <=> (int) preg_replace('/\D/', '', $b));

        $subjects = [];
        foreach ($bySubject as $subject => $values) {
            $subjects[] = ['subject' => $subject, 'values' => $values, 'avg' => round(array_sum($values) / count($values), 2)];
        }
        usort($subjects, static fn ($a, $b) => $b['avg'] <=> $a['avg']);

        return ['columns' => $grades, 'mode' => 'grades', 'subjects' => $subjects, 'overall' => self::mean(array_column($subjects, 'avg'))];
    }

    /** Learning areas across the school year: each term's average and the year's. */
    private function mpsForYear(string $year, array $terms): array
    {
        $bySubject = [];
        foreach ($terms as $term) {
            foreach ($this->mpsForTerm($year, $term)['subjects'] as $s) {
                $bySubject[$s['subject']]['Term ' . $term] = $s['avg'];
            }
        }

        $subjects = [];
        foreach ($bySubject as $subject => $values) {
            $subjects[] = ['subject' => $subject, 'values' => $values, 'avg' => round(array_sum($values) / count($values), 2)];
        }
        usort($subjects, static fn ($a, $b) => $b['avg'] <=> $a['avg']);

        return [
            'columns'  => array_map(static fn ($t) => 'Term ' . $t, $terms),
            'mode'     => 'terms',
            'subjects' => $subjects,
            'overall'  => self::mean(array_column($subjects, 'avg')),
        ];
    }

    /** Classes (grade · subject · section) averaging below 75% — Developing or Emerging. */
    private function classesBelowMastery(string $year, array $terms): array
    {
        $scoreModel = new MpsTestScoreModel();
        $out        = [];
        foreach ($terms as $term) {
            $scores = $scoreModel->forYearTerm($year, $term);
            $cells  = [];
            foreach ($scores as $s) {
                $cells[$s['grade_level'] . '|' . $s['subject'] . '|' . ($s['section'] ?? PerformanceMps::NO_SECTION)] =
                    ['grade' => $s['grade_level'], 'subject' => $s['subject'], 'section' => $s['section']];
            }
            foreach (PerformanceMps::performanceRows($cells, $scores) as $row) {
                if ($row['avg'] !== null && $row['avg'] < 75) {
                    $out[] = $row + ['term' => $term];
                }
            }
        }
        usort($out, static fn ($a, $b) => $a['avg'] <=> $b['avg']);

        return $out;
    }

    /** Tasks due in the period: who was assigned, who submitted (on time / late), who's missing. */
    private function tasks(string $start, string $end, string $asOf): array
    {
        $tasks       = (new TaskModel())->where('deadline >=', $start)->where('deadline <=', $end)->orderBy('deadline', 'ASC')->findAll();
        $users       = new UserModel();
        $assignees   = new TaskAssigneeModel();
        $submissions = new TaskSubmissionModel();
        $names       = array_column($users->select('id, name')->findAll(), 'name', 'id');

        $rows    = [];
        $missing = [];
        $sum     = ['assigned' => 0, 'submitted' => 0, 'onTime' => 0, 'late' => 0, 'missing' => 0];
        foreach ($tasks as $t) {
            $eligible = $t['assigned_role'] === 'specific'
                ? array_map('intval', $assignees->userIdsForTask((int) $t['id']))
                : array_map('intval', array_column($users->active()->where('role', $t['assigned_role'])->findAll(), 'id'));
            $byUser = [];
            foreach ($submissions->forTask((int) $t['id']) as $s) {
                $byUser[(int) $s['user_id']] = $s;
            }

            $due  = $t['deadline'] . ' 23:59:59';
            $past = $t['deadline'] < $asOf;
            $row  = ['title' => $t['title'], 'deadline' => $t['deadline'], 'assigned' => count($eligible), 'submitted' => 0, 'onTime' => 0, 'late' => 0, 'missing' => 0];
            foreach ($eligible as $userId) {
                if (isset($byUser[$userId])) {
                    $row['submitted']++;
                    $byUser[$userId]['submitted_at'] <= $due ? $row['onTime']++ : $row['late']++;
                } elseif ($past) {
                    $row['missing']++;
                    $missing[$userId] = ($missing[$userId] ?? 0) + 1;
                }
            }
            $row['rate'] = $row['assigned'] > 0 ? round($row['submitted'] / $row['assigned'] * 100, 1) : null;
            $rows[]      = $row;
            foreach ($sum as $k => $v) {
                $sum[$k] += $row[$k];
            }
        }

        arsort($missing);
        $missingPeople = [];
        foreach (array_slice($missing, 0, 10, true) as $userId => $count) {
            $missingPeople[] = ['name' => $names[$userId] ?? 'User #' . $userId, 'missing' => $count];
        }

        return [
            'rows'          => $rows,
            'totals'        => $sum,
            'rate'          => $sum['assigned'] > 0 ? round($sum['submitted'] / $sum['assigned'] * 100, 1) : null,
            'missingPeople' => $missingPeople,
        ];
    }

    private static function mean(array $values): ?float
    {
        return $values !== [] ? round(array_sum($values) / count($values), 2) : null;
    }
}
