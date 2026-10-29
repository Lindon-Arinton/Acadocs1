<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Controllers\Teacher\PerformanceMps;
use App\Models\NotificationModel;
use App\Models\SubjectLoadRequestModel;
use App\Models\TeacherModel;
use App\Models\TeacherSubjectModel;
use App\Models\UserModel;

class Profile extends BaseController
{
    private const ALLOWED_PHOTO_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    private const YEAR_PATTERN      = '/^(\d{4})-(\d{4})$/';

    public function index()
    {
        $sessionUser = currentUser();
        $model       = new UserModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax  = $this->request->isAJAX();
            $action  = $this->request->getPost('action');
            $message = null;
            $error   = null;

            try {
                if ($action === 'update_info') {
                    $name  = trim($this->request->getPost('name') ?? '');
                    $email = trim($this->request->getPost('email') ?? '');

                    if ($name === '' || $email === '') {
                        $error = 'Name and email are required.';
                    } elseif ($model->where('email', $email)->where('id !=', $sessionUser['id'])->first()) {
                        $error = 'That email is already in use by another account.';
                    } else {
                        $model->update($sessionUser['id'], ['name' => $name, 'email' => $email]);
                        $this->refreshSession($model, $sessionUser['id']);
                        $message = 'Profile updated successfully.';
                    }
                } elseif ($action === 'change_password') {
                    $current = $this->request->getPost('current_password') ?? '';
                    $new     = $this->request->getPost('new_password') ?? '';
                    $confirm = $this->request->getPost('confirm_password') ?? '';
                    $dbUser  = $model->find($sessionUser['id']);

                    if (! password_verify($current, $dbUser['password'])) {
                        $error = 'Current password is incorrect.';
                    } elseif (strlen($new) < 6) {
                        $error = 'New password must be at least 6 characters.';
                    } elseif ($new !== $confirm) {
                        $error = 'New password and confirmation do not match.';
                    } else {
                        $model->update($sessionUser['id'], ['password' => password_hash($new, PASSWORD_BCRYPT)]);
                        $message = 'Password changed successfully.';
                    }
                } elseif ($action === 'upload_photo') {
                    $rules = [
                        'photo' => [
                            'label'  => 'Photo',
                            'rules'  => 'uploaded[photo]|max_size[photo,2048]|ext_in[photo,' . implode(',', self::ALLOWED_PHOTO_EXT) . ']|is_image[photo]',
                            'errors' => [
                                'uploaded' => 'Please choose an image to upload.',
                                'max_size' => 'Image is too large (max 2MB).',
                                'ext_in'   => 'Unsupported image type.',
                                'is_image' => 'The file must be a valid image.',
                            ],
                        ],
                    ];

                    if (! $this->validate($rules)) {
                        $error = implode(' ', $this->validator->getErrors());
                    } else {
                        $targetDir = FCPATH . 'uploads/avatars';

                        if (! is_dir($targetDir)) {
                            mkdir($targetDir, 0755, true);
                        }

                        $dbUser = $model->find($sessionUser['id']);
                        if (! empty($dbUser['photo']) && is_file($targetDir . DIRECTORY_SEPARATOR . $dbUser['photo'])) {
                            unlink($targetDir . DIRECTORY_SEPARATOR . $dbUser['photo']);
                        }

                        $file    = $this->request->getFile('photo');
                        $newName = 'user_' . $sessionUser['id'] . '_' . $file->getRandomName();
                        $file->move($targetDir, $newName);

                        $model->update($sessionUser['id'], ['photo' => $newName]);
                        $this->refreshSession($model, $sessionUser['id']);
                        $message = 'Profile photo updated successfully.';
                    }
                } elseif ($action === 'remove_photo') {
                    $dbUser    = $model->find($sessionUser['id']);
                    $targetDir = FCPATH . 'uploads/avatars';

                    if (! empty($dbUser['photo']) && is_file($targetDir . DIRECTORY_SEPARATOR . $dbUser['photo'])) {
                        unlink($targetDir . DIRECTORY_SEPARATOR . $dbUser['photo']);
                    }

                    $model->update($sessionUser['id'], ['photo' => null]);
                    $this->refreshSession($model, $sessionUser['id']);
                    $message = 'Profile photo removed.';
                } elseif ($action === 'request_subject' && hasRole('teacher')) {
                    [$message, $error] = $this->requestSubjectChange($sessionUser);
                } elseif ($action === 'cancel_subject_request' && hasRole('teacher')) {
                    [$message, $error] = $this->cancelSubjectRequest($sessionUser);
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/profile');
            }

            if ($error) {
                return $isAjax ? $this->ajaxError($error) : redirect()->to('/profile')->with('flash', ['type' => 'danger', 'msg' => $error]);
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/profile');
        }

        return view('pages/shared/profile', [
            'pageTitle'   => 'My Profile',
            'profile'     => $model->find($sessionUser['id']),
            'subjectLoad' => hasRole('teacher') ? $this->subjectLoad($sessionUser) : null,
            'flash'       => session()->getFlashdata('flash'),
        ]);
    }

    /**
     * The teacher's subject load for the school year + term picked on the
     * page (defaults to the term after the latest one they filled in). When
     * that term has nothing saved yet, the load that applies is the previous
     * term's (or the admin's base load). Teachers don't edit it directly: each
     * add / change / remove is a request form the principal or ADAS approves.
     */
    private function subjectLoad(array $user): ?array
    {
        $teacher = (new TeacherModel())->resolveForUser($user);
        if (! $teacher) {
            return null;
        }

        $subjectModel = new TeacherSubjectModel();
        $teacherId    = (int) $teacher['id'];

        $year = trim($this->request->getGet('sy') ?? '');
        $term = (int) $this->request->getGet('term');
        if (! $this->isValidYear($year) || ! in_array($term, PerformanceMps::TERM_OPTIONS, true)) {
            [$year, $term] = $this->nextTerm($subjectModel->latestTermFor($teacherId));
        }

        $rows   = $subjectModel->forTerm($teacherId, $year, $term);
        $source = 'saved';

        if ($rows === []) {
            $previous = $subjectModel->latestTermFor($teacherId, $year, $term);
            $rows     = $subjectModel->forTeacher($teacherId, $year, $term);
            $source   = $previous
                ? 'Term ' . $previous['term'] . ', SY ' . $previous['school_year']
                : ($rows !== [] ? 'the load set by the admin' : 'none');
        }

        $requests  = (new SubjectLoadRequestModel())->forTeacherTerm($teacherId, $year, $term);
        $reviewers = array_column((new UserModel())->select('id, name')->findAll(), 'name', 'id');
        $pendingBy = []; // row key => pending action, so the row shows "Change requested" etc.
        foreach ($requests as &$r) {
            $r['change']        = SubjectLoadRequestModel::change($r);
            $r['reviewer_name'] = $reviewers[$r['reviewed_by']] ?? null;
            if ($r['status'] === 'Pending' && $r['change']['from']) {
                $pendingBy[SubjectLoadRequestModel::rowKey($r['change']['from'])] = $r['change']['action'];
            }
        }
        unset($r);

        foreach ($rows as &$row) {
            $row['pending'] = $pendingBy[SubjectLoadRequestModel::rowKey($row)] ?? null;
        }
        unset($row);

        $years = PerformanceMps::YEAR_OPTIONS;
        array_unshift($years, $this->shiftYear($years[0], 1));

        return [
            'year'        => $year,
            'term'        => $term,
            'rows'        => $rows,
            'source'      => $source,
            'requests'    => $requests,
            'years'       => array_values(array_unique($years)),
            'terms'       => PerformanceMps::TERM_OPTIONS,
            'gradeLevels' => PerformanceMps::GRADE_LEVELS,
            'subjects'    => PerformanceMps::SUBJECTS,
            'sections'    => $this->knownSections(),
        ];
    }

    /** Sections already used in any subject load, per grade level (suggestions on the form). */
    private function knownSections(): array
    {
        $out = [];
        foreach ((new TeacherSubjectModel())->select('grade_level, section')->distinct()
            ->where('section IS NOT NULL', null, false)->where('section !=', '')->orderBy('section')->findAll() as $r) {
            $out[$r['grade_level']][] = $r['section'];
        }

        return $out;
    }

    /**
     * One add / change / remove request for a subject, from the request form
     * (a reason is required). It applies only when the principal or ADAS
     * approves it on Subject Requests (Admin\SubjectRequests).
     *
     * @return array{0:?string,1:?string} [message, error]
     */
    private function requestSubjectChange(array $user): array
    {
        $year   = trim((string) $this->request->getPost('school_year'));
        $term   = (int) $this->request->getPost('term');
        $action = (string) $this->request->getPost('kind');
        $reason = trim((string) $this->request->getPost('reason'));

        if (! $this->isValidYear($year) || ! in_array($term, PerformanceMps::TERM_OPTIONS, true)) {
            return [null, 'Please pick a valid school year (e.g. 2026-2027) and term.'];
        }
        if (! isset(SubjectLoadRequestModel::ACTIONS[$action])) {
            return [null, 'Unknown request.'];
        }
        if ($reason === '') {
            return [null, 'Please give a reason for the request.'];
        }

        $teacher = (new TeacherModel())->resolveForUser($user);
        if (! $teacher) {
            return [null, 'No teacher record is linked to your account.'];
        }
        $teacherId = (int) $teacher['id'];

        $row = function (string $prefix): ?array {
            $subject = trim((string) $this->request->getPost($prefix . 'subject'));
            $grade   = trim((string) $this->request->getPost($prefix . 'grade'));
            $section = trim((string) $this->request->getPost($prefix . 'section'));

            return $subject === '' && $grade === '' && $section === '' ? null
                : ['subject' => $subject, 'grade_level' => $grade, 'section' => $section];
        };
        $change = ['action' => $action, 'from' => $action === 'add' ? null : $row('from_'), 'to' => $action === 'remove' ? null : $row('to_')];

        if ($change['to'] !== null) {
            if (in_array('', $change['to'], true)) {
                return [null, 'Fill out the subject, grade level and section.'];
            }
            if (! in_array($change['to']['grade_level'], PerformanceMps::GRADE_LEVELS, true)) {
                return [null, 'Pick a grade level from the list.'];
            }
        }
        if ($action !== 'add' && $change['from'] === null) {
            return [null, 'Pick the subject to ' . strtolower(SubjectLoadRequestModel::ACTIONS[$action]) . '.'];
        }
        if ($action === 'change' && SubjectLoadRequestModel::rowKey($change['from']) === SubjectLoadRequestModel::rowKey($change['to'])) {
            return [null, 'Nothing changed: edit the subject, grade level or section.'];
        }

        // Must fit the load as it is now (it may already have changed).
        $current = (new TeacherSubjectModel())->forTeacher($teacherId, $year, $term);
        [, $error] = SubjectLoadRequestModel::apply($current, $change);
        if ($error) {
            return [null, $error];
        }

        // One open request per subject: no second change/remove of the same row, no duplicate add.
        $requests = new SubjectLoadRequestModel();
        foreach ($requests->forTeacherTerm($teacherId, $year, $term, 0) as $p) {
            $pc = SubjectLoadRequestModel::change($p);
            foreach (['from', 'to'] as $side) {
                if ($change[$side] && $pc[$side] && SubjectLoadRequestModel::rowKey($change[$side]) === SubjectLoadRequestModel::rowKey($pc[$side])) {
                    return [null, 'You already have a pending request for ' . SubjectLoadRequestModel::label($change[$side]) . '. Cancel it first to send a new one.'];
                }
            }
        }

        $requestId = (int) $requests->insert([
            'teacher_id'  => $teacherId,
            'user_id'     => (int) $user['id'],
            'school_year' => $year,
            'term'        => $term,
            'proposed'    => json_encode($change),
            'reason'      => mb_substr($reason, 0, 1000),
            'status'      => 'Pending',
        ]);

        $what = match ($action) {
            'add'    => 'Add ' . SubjectLoadRequestModel::label($change['to']),
            'change' => 'Change ' . SubjectLoadRequestModel::label($change['from']) . ' → ' . SubjectLoadRequestModel::label($change['to']),
            default  => 'Remove ' . SubjectLoadRequestModel::label($change['from']),
        };
        $notifications = new NotificationModel();
        foreach ((new UserModel())->active()->whereIn('role', ['admin', 'adas'])->findAll() as $approver) {
            $notifications->upsertGrouped(
                (int) $approver['id'],
                'subject_request',
                $requestId,
                'subject_request',
                'Subject request: ' . ($user['name'] ?? 'Teacher'),
                mb_strimwidth($what . ' (Term ' . $term . ', SY ' . $year . ')', 0, 250, '…'),
                base_url('subject-requests')
            );
        }

        return ['Request sent to the principal / ADAS. Your subjects change once it is approved.', null];
    }

    /** @return array{0:?string,1:?string} [message, error] */
    private function cancelSubjectRequest(array $user): array
    {
        $teacher = (new TeacherModel())->resolveForUser($user);
        $request = $teacher ? (new SubjectLoadRequestModel())->find((int) $this->request->getPost('request_id')) : null;

        if (! $request || (int) $request['teacher_id'] !== (int) $teacher['id'] || $request['status'] !== 'Pending') {
            return [null, 'That request can no longer be cancelled.'];
        }

        (new NotificationModel())->where('ref_type', 'subject_request')->where('ref_id', (int) $request['id'])->delete();
        (new SubjectLoadRequestModel())->delete((int) $request['id']);

        return ['Request cancelled.', null];
    }

    private function isValidYear(string $year): bool
    {
        return preg_match(self::YEAR_PATTERN, $year, $m) === 1 && (int) $m[2] === (int) $m[1] + 1;
    }

    private function shiftYear(string $year, int $by): string
    {
        $start = (int) substr($year, 0, 4) + $by;

        return $start . '-' . ($start + 1);
    }

    /**
     * The term after the given one (Term 3 rolls into Term 1 of the next
     * school year); the current school year's Term 1 when there is none.
     *
     * @return array{0:string,1:int}
     */
    private function nextTerm(?array $latest): array
    {
        if (! $latest) {
            return [PerformanceMps::YEAR_OPTIONS[0], PerformanceMps::TERM_OPTIONS[0]];
        }

        $term = (int) $latest['term'];

        return $term < max(PerformanceMps::TERM_OPTIONS)
            ? [$latest['school_year'], $term + 1]
            : [$this->shiftYear($latest['school_year'], 1), PerformanceMps::TERM_OPTIONS[0]];
    }

    private function refreshSession(UserModel $model, int $userId): void
    {
        $fresh = $model->find($userId);
        unset($fresh['password']);
        session()->set('user', $fresh);
    }
}
