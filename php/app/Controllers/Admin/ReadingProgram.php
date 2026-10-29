<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use App\Models\ReadingResultModel;

/**
 * The principal's Reading Program page (sidebar → Reading Program):
 *  - assigns who encodes Phil-IRI Filipino / English (Teacher\Reading);
 *  - monitors encoding progress section by section, approves or returns them;
 *  - charts and a summary sheet of the submitted / approved results.
 */
class ReadingProgram extends BaseController
{
    public function index()
    {
        if (! hasRole('admin')) {
            return redirect()->to('/dashboard');
        }
        if ($this->request->getMethod() === 'POST') {
            return $this->act();
        }

        [$year, $period, $years] = ReadingResultModel::yearPeriod($this->request->getGet('year'), $this->request->getGet('period'));
        $language = (string) $this->request->getGet('language');
        if (! in_array($language, ReadingResultModel::LANGUAGES, true)) {
            $language = ReadingResultModel::LANGUAGES[0];
        }

        $db       = db_connect();
        $rows     = (new ReadingResultModel())->keyed($year, $period, $language);
        $sections = ReadingResultModel::sectionsWithRows($rows);
        $names    = array_column($db->query('SELECT id, name FROM users')->getResultArray(), 'name', 'id');

        $assignments = array_fill_keys(ReadingResultModel::LANGUAGES, []);
        foreach ($db->query('SELECT a.id, a.language, a.user_id, a.created_at, u.name FROM reading_assignments a JOIN users u ON u.id = a.user_id ORDER BY u.name')->getResultArray() as $a) {
            $assignments[$a['language']][] = $a;
        }

        return view('pages/admin/reading_program', [
            'pageTitle'   => 'Reading Program',
            'year'        => $year,
            'period'      => $period,
            'years'       => $years,
            'language'    => $language,
            'rows'        => $rows,
            'sections'    => $sections,
            'names'       => $names,
            'assignments' => $assignments,
            'teachers'    => $db->query("SELECT id, name FROM users WHERE role = 'teacher' AND is_active = 1 ORDER BY name")->getResultArray(),
            'summary'     => ReadingResultModel::summary($year, $period, $language),
            // Beginning vs Year End of the same school year, for the progress chart.
            'compare'     => [
                'Beginning' => ReadingResultModel::summary($year, 'Beginning', $language),
                'End'       => ReadingResultModel::summary($year, 'End', $language),
            ],
        ]);
    }

    private function act()
    {
        $action        = (string) $this->request->getPost('action');
        $notifications = new NotificationModel();
        $me            = (int) currentUser()['id'];
        $db            = db_connect();

        if ($action === 'assign' || $action === 'unassign') {
            if ($action === 'assign') {
                $language = (string) $this->request->getPost('language');
                $userId   = (int) $this->request->getPost('user_id');
                $teacher  = $db->query("SELECT id, name FROM users WHERE id = ? AND role = 'teacher' AND is_active = 1", [$userId])->getRowArray();
                if (! in_array($language, ReadingResultModel::LANGUAGES, true) || ! $teacher) {
                    return $this->ajaxError('Pick a language and an active teacher.');
                }
                if ($db->query('SELECT 1 FROM reading_assignments WHERE language = ? AND user_id = ?', [$language, $userId])->getRow()) {
                    return $this->ajaxError($teacher['name'] . ' already handles Phil-IRI ' . $language . '.');
                }
                $db->table('reading_assignments')->insert(['language' => $language, 'user_id' => $userId, 'assigned_by' => $me]);
                $notifications->upsertGrouped(
                    $userId, 'reading_assign', (int) $db->insertID(), 'reading',
                    'You were assigned to encode Phil-IRI ' . $language,
                    'Reading Program · all Grade 7–10 sections',
                    base_url('reading?language=' . $language)
                );

                return $this->ajaxSuccess($teacher['name'] . ' now handles Phil-IRI ' . $language . '.');
            }

            $a = $db->query('SELECT a.*, u.name FROM reading_assignments a JOIN users u ON u.id = a.user_id WHERE a.id = ?', [(int) $this->request->getPost('assignment_id')])->getRowArray();
            if (! $a) {
                return $this->ajaxError('That assignment no longer exists.');
            }
            $db->table('reading_assignments')->delete(['id' => (int) $a['id']]);
            $notifications->where('ref_type', 'reading_assign')->where('ref_id', (int) $a['id'])->delete();

            return $this->ajaxSuccess($a['name'] . ' no longer handles Phil-IRI ' . $a['language'] . '. What they encoded stays.');
        }

        $model = new ReadingResultModel();
        if ($action === 'approve_all') {
            $targets = $model->where('school_year', (string) $this->request->getPost('year'))
                ->where('period', (string) $this->request->getPost('period'))
                ->where('language', (string) $this->request->getPost('language'))
                ->where('status', 'Submitted')->findAll();
        } else {
            $targets = array_filter([$model->find((int) $this->request->getPost('result_id'))]);
        }
        if (! in_array($action, ['approve', 'return', 'approve_all'], true) || $targets === []) {
            return $this->ajaxError('Nothing to review.');
        }

        $note = trim((string) $this->request->getPost('note'));
        if ($action === 'return' && $note === '') {
            return $this->ajaxError('Say what needs fixing so the encoder knows.');
        }
        $status = $action === 'return' ? 'Returned' : 'Approved';
        foreach ($targets as $r) {
            if ($r['status'] !== 'Submitted' && ! ($status === 'Returned' && $r['status'] === 'Approved')) {
                return $this->ajaxError($r['grade_level'] . ' ' . $r['section'] . ' is ' . strtolower($r['status']) . ', not waiting for review.');
            }
            $model->update((int) $r['id'], [
                'status'      => $status,
                'reviewed_by' => $me,
                'reviewed_at' => date('Y-m-d H:i:s'),
                'review_note' => $status === 'Returned' ? mb_substr($note, 0, 500) : null,
            ]);
            $notifications->where('ref_type', 'reading_submit')->where('ref_id', (int) $r['id'])->delete();
            if ($r['updated_by']) {
                $notifications->upsertGrouped(
                    (int) $r['updated_by'], 'reading_review', (int) $r['id'], 'reading',
                    'Phil-IRI ' . $r['language'] . ' ' . ($status === 'Approved' ? 'approved' : 'returned') . ': ' . $r['grade_level'] . ' ' . $r['section'],
                    mb_strimwidth(($status === 'Returned' ? $note . ' · ' : '') . ReadingResultModel::PERIODS[$r['period']] . ', SY ' . $r['school_year'], 0, 250, '…'),
                    base_url('reading?' . http_build_query(['year' => $r['school_year'], 'period' => $r['period'], 'language' => $r['language'], 'grade' => $r['grade_level'], 'section' => $r['section']]))
                );
            }
        }

        $n = count($targets);

        return $this->ajaxSuccess($status === 'Approved'
            ? ($n === 1 ? 'Approved.' : $n . ' sections approved.')
            : 'Returned to the encoder with your note.');
    }
}
