<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use App\Models\ReadingResultModel;

/**
 * Reading Program encoding (Phil-IRI Filipino / English): the teacher the
 * principal assigned to a language encodes every Grade 7–10 section in the
 * school (it's a school-wide assessment), one section at a time: Save Draft,
 * then Submit for Review once every table adds up. The principal can open the
 * same page read-only to review a section (Admin\ReadingProgram approves).
 */
class Reading extends BaseController
{
    public function index()
    {
        $user        = currentUser();
        $isPrincipal = hasRole('admin');
        $assigned    = ReadingResultModel::assignedLanguages((int) $user['id']);
        $languages   = $isPrincipal ? ReadingResultModel::LANGUAGES : $assigned;
        if ($languages === []) {
            return redirect()->to('/dashboard');
        }

        $source = $this->request->getMethod() === 'POST' ? 'getPost' : 'getGet';
        [$year, $period, $years] = ReadingResultModel::yearPeriod($this->request->{$source}('year'), $this->request->{$source}('period'));
        $language = (string) $this->request->{$source}('language');
        if ($source === 'getPost' && ! in_array($language, $assigned, true)) {
            return $this->ajaxError('Only the teacher assigned to Phil-IRI ' . ($language ?: 'this language') . ' can encode it.', 403);
        }
        if (! in_array($language, $languages, true)) {
            $language = $languages[0];
        }

        $model    = new ReadingResultModel();
        $rows     = $model->keyed($year, $period, $language);
        $sections = ReadingResultModel::sectionsWithRows($rows);

        $grade = (string) $this->request->{$source}('grade');
        if (! isset($sections[$grade])) {
            $grade = array_key_first(array_filter($sections)) ?? 'Grade 7';
        }
        $section = (string) $this->request->{$source}('section');
        if (! in_array($section, $sections[$grade], true)) {
            $section = $sections[$grade][0] ?? '';
        }
        $row     = $rows[$grade . '|' . $section] ?? null;
        $canEdit = in_array($language, $assigned, true) && $section !== '' && ! in_array($row['status'] ?? '', ['Submitted', 'Approved'], true);

        if ($this->request->getMethod() === 'POST') {
            return $this->save($model, $row, compact('year', 'period', 'language', 'grade', 'section'));
        }

        $done = [];
        foreach ($sections as $g => $list) {
            $done[$g] = count(array_filter($list, static fn ($s) => in_array($rows[$g . '|' . $s]['status'] ?? '', ['Submitted', 'Approved'], true)));
        }

        return view('pages/teacher/reading', [
            'pageTitle'   => 'Reading Program',
            'year'        => $year,
            'period'      => $period,
            'years'       => $years,
            'language'    => $language,
            'languages'   => $languages,
            'grade'       => $grade,
            'section'     => $section,
            'sections'    => $sections,
            'rows'        => $rows,
            'row'         => $row,
            'done'        => $done,
            'canEdit'     => $canEdit,
            'isPrincipal' => $isPrincipal,
            'updatedBy'   => $row && $row['updated_by'] ? (db_connect()->query('SELECT name FROM users WHERE id = ?', [(int) $row['updated_by']])->getRow()->name ?? null) : null,
        ]);
    }

    /**
     * Printable Phil-IRI report with the DepEd / school letterhead, for the
     * principal and the teachers assigned to that language. Counts the
     * submitted and approved sections only, like the principal's charts.
     */
    public function report()
    {
        $user        = currentUser();
        $isPrincipal = hasRole('admin');
        $assigned    = ReadingResultModel::assignedLanguages((int) $user['id']);
        $languages   = $isPrincipal ? ReadingResultModel::LANGUAGES : $assigned;
        $language    = (string) $this->request->getGet('language');
        if (! in_array($language, $languages, true)) {
            if ($languages === []) {
                return redirect()->to('/dashboard');
            }
            $language = $languages[0];
        }
        [$year, $period] = ReadingResultModel::yearPeriod($this->request->getGet('year'), $this->request->getGet('period'));

        $db       = db_connect();
        $rows     = (new ReadingResultModel())->keyed($year, $period, $language);
        $sections = ReadingResultModel::sectionsWithRows($rows);
        $reported = [];
        foreach ($sections as $g => $list) {
            foreach ($list as $s) {
                $r = $rows[$g . '|' . $s] ?? null;
                if ($r && in_array($r['status'], ['Submitted', 'Approved'], true)) {
                    $reported[] = $r;
                }
            }
        }

        $principal = $db->query("SELECT name FROM users WHERE role = 'admin' AND is_active = 1 ORDER BY id LIMIT 1")->getRow()->name ?? '';
        $encoders  = array_column($db->query('SELECT u.name FROM reading_assignments a JOIN users u ON u.id = a.user_id WHERE a.language = ? ORDER BY u.name', [$language])->getResultArray(), 'name');
        // The principal printing it: the assigned encoder still signs as preparer.
        $preparedBy = $isPrincipal ? ($encoders[0] ?? $user['name']) : $user['name'];

        return view('pages/teacher/reading_report', [
            'year'          => $year,
            'period'        => $period,
            'language'      => $language,
            'summary'       => ReadingResultModel::summary($year, $period, $language),
            'compare'       => [
                'Beginning' => ReadingResultModel::summary($year, 'Beginning', $language),
                'End'       => ReadingResultModel::summary($year, 'End', $language),
            ],
            'reported'      => $reported,
            'sectionsTotal' => array_sum(array_map('count', $sections)),
            'preparedBy'    => $preparedBy,
            'preparedRole'  => $isPrincipal && $encoders === [] ? 'School Principal' : 'Phil-IRI ' . $language . ' Coordinator',
            'principalName' => $principal,
            'generatedBy'   => $user['name'],
        ]);
    }

    private function save(ReadingResultModel $model, ?array $row, array $where)
    {
        if ($where['section'] === '') {
            return $this->ajaxError('Pick a section first.');
        }
        if ($row && in_array($row['status'], ['Submitted', 'Approved'], true)) {
            return $this->ajaxError('This section was already ' . strtolower($row['status']) . '. Ask the principal to return it if something needs fixing.');
        }

        $data = [];
        foreach (['enrolled_m', 'enrolled_f', 'tested_m', 'tested_f'] as $f) {
            $v = trim((string) $this->request->getPost($f));
            if ($v !== '' && (! ctype_digit($v) || (int) $v > 9999)) {
                return $this->ajaxError('Learner counts must be whole numbers.');
            }
            $data[$f] = $v === '' ? null : (int) $v;
        }

        // Only the known tables / levels / sexes are kept (a crafted post adds nothing).
        $input  = (array) $this->request->getPost('counts');
        $counts = [];
        foreach (ReadingResultModel::tables($where['language']) as $key => $t) {
            foreach (array_keys($t['levels']) as $lv) {
                foreach (['m', 'f'] as $sex) {
                    $v = trim((string) ($input[$key][$lv][$sex] ?? ''));
                    if ($v === '') {
                        continue;
                    }
                    if (! ctype_digit($v) || (int) $v > 9999) {
                        return $this->ajaxError($t['title'] . ': counts must be whole numbers.');
                    }
                    $counts[$key][$lv][$sex] = (int) $v;
                }
            }
        }
        $data['counts'] = json_encode($counts);

        $submit = $this->request->getPost('intent') === 'submit';
        if ($submit && ($problems = ReadingResultModel::problems($data + ['language' => $where['language']]))) {
            return $this->ajaxError('Not ready to submit: ' . implode(' ', $problems));
        }

        $now  = date('Y-m-d H:i:s');
        $data += ['updated_by' => (int) currentUser()['id'], 'updated_at' => $now];
        if ($submit) {
            $data += ['status' => 'Submitted', 'submitted_at' => $now, 'review_note' => null];
        } elseif (! $row) {
            $data['status'] = 'Draft';
        } // a Returned section stays Returned (with the principal's note) until it's resubmitted

        if ($row) {
            $model->update((int) $row['id'], $data);
            $id = (int) $row['id'];
        } else {
            $id = (int) $model->insert($data + [
                'school_year' => $where['year'], 'period' => $where['period'], 'language' => $where['language'],
                'grade_level' => $where['grade'], 'section' => $where['section'],
            ]);
        }

        $label = 'Phil-IRI ' . $where['language'] . ' · ' . $where['grade'] . ' ' . $where['section'];
        if ($submit) {
            $notifications = new NotificationModel();
            foreach (ReadingResultModel::principalIds() as $pid) {
                $notifications->upsertGrouped(
                    $pid, 'reading_submit', $id, 'reading',
                    'Reading results for review: ' . $label,
                    currentUser()['name'] . ' · ' . ReadingResultModel::PERIODS[$where['period']] . ', SY ' . $where['year'],
                    base_url('reading-program?' . http_build_query(['year' => $where['year'], 'period' => $where['period'], 'language' => $where['language']])) . '#reading-sections'
                );
            }
        }

        return $this->ajaxSuccess($submit
            ? $label . ' submitted. The principal has been notified.'
            : 'Draft saved for ' . $label . '.');
    }
}
