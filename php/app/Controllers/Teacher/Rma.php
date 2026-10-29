<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Libraries\SchoolCalendar;
use App\Models\RmaResultModel;
use App\Models\TeacherModel;
use CodeIgniter\Database\RawSql;

/**
 * Rapid Mathematics Assessment (RMA) entry for Math teachers: one Male and
 * one Female row per section they teach Mathematics to (from their subject
 * load). They can only enter data for those sections; the school-wide table
 * is on the principal's dashboard (Admin\Dashboard).
 */
class Rma extends BaseController
{
    public function index()
    {
        if (! hasRole('teacher')) {
            return redirect()->to('/dashboard');
        }

        $today  = (new SchoolCalendar())->termOn();
        $year   = trim((string) ($this->request->getPost('school_year') ?? $this->request->getGet('year') ?? ''));
        $period = (string) ($this->request->getPost('period') ?? $this->request->getGet('period') ?? '');
        if (! preg_match('/^(\d{4})-(\d{4})$/', $year, $m) || (int) $m[2] !== (int) $m[1] + 1) {
            $year = $today['schoolYear'] ?? PerformanceMps::YEAR_OPTIONS[0];
        }
        if (! isset(RmaResultModel::PERIODS[$period])) {
            // Term 1 → pre-test, Term 2 → mid-year, Term 3 → post-test.
            $period = ['1' => 'BoSY', '2' => 'MoSY', '3' => 'EoSY'][(string) ($today['term'] ?? 1)] ?? 'BoSY';
        }

        $sections = self::mathSectionsFor(currentUser(), $year);

        if ($this->request->getMethod() === 'POST') {
            return $this->save($year, $period, $sections);
        }

        $years = PerformanceMps::YEAR_OPTIONS;
        if (! in_array($year, $years, true)) {
            array_unshift($years, $year);
        }

        return view('pages/teacher/rma', [
            'pageTitle' => 'Rapid Mathematics Assessment',
            'year'      => $year,
            'period'    => $period,
            'years'     => $years,
            'sections'  => $sections,
            'rows'      => (new RmaResultModel())->keyed($year, $period),
            'flash'     => session()->getFlashdata('flash'),
        ]);
    }

    /**
     * The sections a teacher teaches Mathematics to in a school year (any of
     * its terms' subject loads, or the admin's base load).
     *
     * @return list<array{grade:string,section:string}>
     */
    public static function mathSectionsFor(?array $user, string $year): array
    {
        $teacher = $user ? (new TeacherModel())->resolveForUser($user) : null;
        if (! $teacher) {
            return [];
        }

        $mps   = new PerformanceMps();
        $found = [];
        foreach (PerformanceMps::TERM_OPTIONS as $term) {
            foreach ($mps->handledCellsForTeacher((int) $teacher['id'], $year, $term) as $cell) {
                if (RmaResultModel::isMathSubject($cell['subject']) && ! empty($cell['section'])) {
                    $found[] = ['grade' => $cell['grade'], 'section' => $cell['section']];
                }
            }
        }

        return RmaResultModel::sortSections($found);
    }

    private function save(string $year, string $period, array $sections)
    {
        $isAjax  = $this->request->isAJAX();
        $allowed = [];
        foreach ($sections as $s) {
            $allowed[$s['grade'] . '|' . $s['section']] = $s;
        }
        if ($allowed === []) {
            return $this->ajaxError('You have no Mathematics sections in your subject load for SY ' . $year . '.');
        }

        $model    = new RmaResultModel();
        $existing = $model->keyed($year, $period);
        $saved    = 0;
        $errors   = [];
        $db       = db_connect();
        $db->transBegin();

        foreach ((array) $this->request->getPost('rows') as $key => $input) {
            [$grade, $section, $sex] = array_pad(explode('|', (string) $key, 3), 3, '');
            // Only the teacher's own Math sections (a crafted post can't reach others).
            if (! isset($allowed[$grade . '|' . $section]) || ! in_array($sex, RmaResultModel::SEXES, true)) {
                continue;
            }
            $label = $section . ' (' . $sex . ')';

            $data  = [];
            $blank = true;
            foreach (RmaResultModel::COUNT_FIELDS as $f) {
                $v = trim((string) ($input[$f] ?? ''));
                if ($v === '') {
                    $data[$f] = null;
                    continue;
                }
                if (! ctype_digit($v) || (int) $v > 9999) {
                    $errors[] = $label . ': counts must be whole numbers.';
                    continue 2;
                }
                $data[$f] = (int) $v;
                $blank    = false;
            }
            foreach (RmaResultModel::SCORE_FIELDS as $f) {
                $v = trim((string) ($input[$f] ?? ''));
                if ($v === '') {
                    $data[$f] = null;
                    continue;
                }
                if (! is_numeric($v) || (float) $v < 0 || (float) $v > 9999) {
                    $errors[] = $label . ': scores must be numbers of 0 or more.';
                    continue 2;
                }
                $data[$f] = round((float) $v, 2);
                $blank    = false;
            }
            if ($data['enrolment'] !== null && $data['assessed'] !== null && $data['assessed'] > $data['enrolment']) {
                $errors[] = $label . ': learners assessed can\'t be more than the enrolment.';
                continue;
            }

            $row = $existing[$grade . '|' . $section . '|' . $sex] ?? null;
            if ($blank) {
                if ($row) {
                    $model->delete((int) $row['id']); // cleared on the form
                }
                continue;
            }

            $data += ['entered_by' => (int) currentUser()['id'], 'updated_at' => new RawSql('NOW()')];
            if ($row) {
                $model->update((int) $row['id'], $data);
            } else {
                $model->insert($data + ['school_year' => $year, 'period' => $period, 'grade_level' => $grade, 'section' => $section, 'sex' => $sex]);
            }
            $saved++;
        }

        if ($errors) {
            $db->transRollback();

            return $isAjax ? $this->ajaxError(implode(' ', array_unique($errors))) : redirect()->back();
        }
        $db->transCommit();

        $message = 'RMA results saved for ' . RmaResultModel::PERIODS[$period] . ', SY ' . $year . '.';

        return $isAjax ? $this->ajaxSuccess($message) : redirect()->to('/rma?year=' . urlencode($year) . '&period=' . $period)->with('flash', ['type' => 'success', 'msg' => $message]);
    }
}
