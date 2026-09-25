<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Controllers\Teacher\PerformanceMps;
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
                } elseif ($action === 'save_subjects' && hasRole('teacher')) {
                    [$message, $error] = $this->saveSubjectLoad($sessionUser);
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
     * that term has nothing saved yet, the form is pre-filled from the load
     * that would otherwise apply, so only what changed needs editing.
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

        $years = PerformanceMps::YEAR_OPTIONS;
        array_unshift($years, $this->shiftYear($years[0], 1));

        return [
            'year'        => $year,
            'term'        => $term,
            'rows'        => $rows,
            'source'      => $source,
            'years'       => array_values(array_unique($years)),
            'terms'       => PerformanceMps::TERM_OPTIONS,
            'gradeLevels' => PerformanceMps::GRADE_LEVELS,
            'subjects'    => PerformanceMps::SUBJECTS,
        ];
    }

    /** @return array{0:?string,1:?string} [message, error] */
    private function saveSubjectLoad(array $user): array
    {
        $year = trim($this->request->getPost('school_year') ?? '');
        $term = (int) $this->request->getPost('term');

        if (! $this->isValidYear($year) || ! in_array($term, PerformanceMps::TERM_OPTIONS, true)) {
            return [null, 'Please pick a valid school year (e.g. 2026-2027) and term.'];
        }

        $teacher = (new TeacherModel())->resolveForUser($user);
        if (! $teacher) {
            return [null, 'No teacher record is linked to your account.'];
        }

        $rows = [];
        foreach ($this->request->getPost('subjects') ?? [] as $row) {
            $subject = trim((string) ($row['subject'] ?? ''));
            $grade   = trim((string) ($row['grade'] ?? ''));
            $section = trim((string) ($row['section'] ?? ''));

            if ($subject === '' && $grade === '' && $section === '') {
                continue;
            }
            if ($subject === '' || $grade === '' || $section === '') {
                return [null, 'Each subject needs a subject, grade level and section.'];
            }
            if (! in_array($grade, PerformanceMps::GRADE_LEVELS, true)) {
                return [null, 'Invalid grade level: ' . $grade];
            }

            $key        = strtolower($subject . '|' . $grade . '|' . $section);
            $rows[$key] = ['subject' => $subject, 'grade_level' => $grade, 'section' => $section];
        }

        if ($rows === []) {
            return [null, 'Add at least one subject for this term.'];
        }

        (new TeacherSubjectModel())->replaceTerm((int) $teacher['id'], $year, $term, array_values($rows));

        return ['Subject load saved for Term ' . $term . ', SY ' . $year . '.', null];
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
