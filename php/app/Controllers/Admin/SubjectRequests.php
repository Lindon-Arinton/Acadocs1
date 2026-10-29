<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RmaResultModel;
use App\Models\NotificationModel;
use App\Models\SubjectLoadRequestModel;
use App\Models\TeacherSubjectModel;
use App\Models\UserModel;

/**
 * Teachers' requests to add, change or remove one subject in their load
 * (request forms on My Profile → Subject Load). The principal or ADAS
 * approves — the change is applied to the teacher's load for that term —
 * or rejects with a reason.
 */
class SubjectRequests extends BaseController
{
    public function index()
    {
        if (! hasRole('admin', 'adas')) {
            return redirect()->to('/dashboard');
        }

        if ($this->request->getMethod() === 'POST') {
            return $this->review();
        }

        $requests = new SubjectLoadRequestModel();
        $loads    = new TeacherSubjectModel();
        $rows     = $requests
            ->select('subject_load_requests.*, teachers.name AS teacher_name, reviewer.name AS reviewer_name')
            ->join('teachers', 'teachers.id = subject_load_requests.teacher_id')
            ->join('users reviewer', 'reviewer.id = subject_load_requests.reviewed_by', 'left')
            ->groupStart()
                ->where('subject_load_requests.status', 'Pending')
                ->orWhere('subject_load_requests.reviewed_at >=', date('Y-m-d H:i:s', strtotime('-60 days')))
            ->groupEnd()
            ->orderBy("subject_load_requests.status = 'Pending'", 'DESC', false)
            ->orderBy('subject_load_requests.id', 'DESC')
            ->findAll();

        foreach ($rows as &$r) {
            $r['change'] = SubjectLoadRequestModel::change($r);
            if ($r['status'] === 'Pending') {
                // Still fits the load as it is right now? (It may have changed since.)
                [, $r['conflict']] = SubjectLoadRequestModel::apply($loads->forTeacher((int) $r['teacher_id'], $r['school_year'], (int) $r['term']), $r['change']);
            }
        }
        unset($r);

        $pending = array_values(array_filter($rows, static fn ($r) => $r['status'] === 'Pending'));

        return view('pages/admin/subject_requests', [
            'pageTitle' => 'Subject Requests',
            'pending'   => $pending,
            'decided'   => array_values(array_filter($rows, static fn ($r) => $r['status'] !== 'Pending')),
            'flash'     => session()->getFlashdata('flash'),
        ]);
    }

    private function review()
    {
        $isAjax   = $this->request->isAJAX();
        $requests = new SubjectLoadRequestModel();
        $request  = $requests->find((int) $this->request->getPost('request_id'));
        $decision = $this->request->getPost('decision');
        $note     = trim((string) $this->request->getPost('note'));

        if (! $request || $request['status'] !== 'Pending' || ! in_array($decision, ['approve', 'reject'], true)) {
            return $this->done($isAjax, null, 'This request was already handled or no longer exists.');
        }
        if ($decision === 'reject' && $note === '') {
            return $this->done($isAjax, null, 'Please say why, so the teacher knows what to fix.');
        }

        try {
            if ($decision === 'approve') {
                // Apply the one change to the load that applies now and save it for that term.
                $loads = new TeacherSubjectModel();
                [$rows, $conflict] = SubjectLoadRequestModel::apply(
                    $loads->forTeacher((int) $request['teacher_id'], $request['school_year'], (int) $request['term']),
                    SubjectLoadRequestModel::change($request)
                );
                if ($conflict) {
                    return $this->done($isAjax, null, "Can't approve: " . $conflict . ' Reject it instead.');
                }
                $hadMath = RmaResultModel::teacherHasMath((int) $request['teacher_id']);
                $loads->replaceTerm((int) $request['teacher_id'], $request['school_year'], (int) $request['term'], $rows);
                RmaResultModel::announceIfNewMath((int) $request['teacher_id'], $hadMath);
            }

            $status = $decision === 'approve' ? 'Approved' : 'Rejected';
            $requests->update($request['id'], [
                'status'      => $status,
                'reviewed_by' => (int) currentUser()['id'],
                'reviewed_at' => date('Y-m-d H:i:s'),
                'review_note' => $note !== '' ? $note : null,
            ]);

            // The approvers' "new request" alerts are done; tell the teacher the outcome.
            $notifications = new NotificationModel();
            $notifications->where('ref_type', 'subject_request')->where('ref_id', (int) $request['id'])->delete();
            $notifications->upsertGrouped(
                (int) $request['user_id'],
                'subject_request_result',
                (int) $request['id'],
                'subject_request',
                'Subject request ' . ($status === 'Approved' ? 'approved' : 'not approved') . ': ' . self::describe(SubjectLoadRequestModel::change($request)),
                mb_strimwidth(($note !== '' ? $note . ' · ' : '') . 'Term ' . $request['term'] . ', SY ' . $request['school_year'], 0, 250, '…'),
                base_url('profile?sy=' . urlencode($request['school_year']) . '&term=' . (int) $request['term']) . '#subject-load'
            );
        } catch (\Throwable $e) {
            return $this->done($isAjax, null, 'Something went wrong: ' . $e->getMessage());
        }

        $teacher = (new UserModel())->find((int) $request['user_id'])['name'] ?? 'the teacher';

        return $this->done($isAjax, $decision === 'approve'
            ? 'Approved. ' . $teacher . '\'s subject load for Term ' . $request['term'] . ', SY ' . $request['school_year'] . ' is updated.'
            : 'Rejected. ' . $teacher . ' has been told why.', null);
    }

    /** "Add English · Grade 8 · Matipid", "Change … → …", "Remove …" (notification titles). */
    private static function describe(array $c): string
    {
        $text = match ($c['action']) {
            'add'    => 'Add ' . SubjectLoadRequestModel::label($c['to']),
            'change' => 'Change ' . SubjectLoadRequestModel::label($c['from']) . ' → ' . SubjectLoadRequestModel::label($c['to']),
            default  => 'Remove ' . SubjectLoadRequestModel::label($c['from']),
        };

        return mb_strimwidth($text, 0, 200, '…');
    }

    private function done(bool $isAjax, ?string $message, ?string $error)
    {
        if ($isAjax) {
            return $error ? $this->ajaxError($error) : $this->ajaxSuccess($message);
        }
        session()->setFlashdata('flash', ['type' => $error ? 'danger' : 'success', 'msg' => $error ?? $message]);

        return redirect()->to('/subject-requests');
    }
}
