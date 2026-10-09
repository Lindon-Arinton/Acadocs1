<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeacherModel;
use App\Models\TeacherSubjectModel;
use App\Models\BiometricEmployeeModel;
use App\Models\TimeRecordModel;
use App\Models\UserModel;

class Users extends BaseController
{
    public const GRADE_LEVELS = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];

    public function index()
    {
        if (! hasRole('admin', 'adas')) {
            return redirect()->to('/dashboard');
        }

        $model = new UserModel();

        if ($this->request->getMethod() === 'POST') {
            $action  = $this->request->getPost('action');
            $isAjax  = $this->request->isAJAX();
            $message = null;

            // ADAS can manage teacher/ADAS accounts but not admins — otherwise
            // they could create an admin account or promote themselves.
            if (! hasRole('admin')) {
                $target = $this->request->getPost('id') ? $model->find((int) $this->request->getPost('id')) : null;
                if ($this->request->getPost('role') === 'admin' || ($target['role'] ?? null) === 'admin') {
                    return $isAjax ? $this->ajaxError('Only an admin can manage admin accounts.', 403) : redirect()->to('/users');
                }
            }

            // Biometric number (scanner "AC-No"): optional, digits only, one account per number.
            $acNo = trim((string) $this->request->getPost('ac_no'));
            if (in_array($action, ['add', 'edit'], true) && $acNo !== '') {
                if (! ctype_digit($acNo) || strlen($acNo) > 20) {
                    return $isAjax ? $this->ajaxError('Biometric No. must be digits only (e.g. 37).') : redirect()->to('/users');
                }
                $acNo  = ltrim($acNo, '0') ?: '0'; // the scanner exports "037" and "37" alike
                $taken = $model->where('ac_no', $acNo)->where('id !=', (int) ($this->request->getPost('id') ?? 0))->first();
                if ($taken) {
                    return $isAjax
                        ? $this->ajaxError('Biometric No. ' . $acNo . ' is already assigned to ' . $taken['name'] . '.')
                        : redirect()->to('/users');
                }
            }

            try {
                if ($action === 'add') {
                    $role = $this->request->getPost('role');

                    $userId = $model->insert([
                        'name'     => $this->request->getPost('name'),
                        'email'    => $this->request->getPost('email'),
                        'password' => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT),
                        'role'     => $role,
                        'ac_no'    => $acNo !== '' ? $acNo : null,
                    ]);
                    $linked = $acNo !== '' ? $this->linkBiometric((int) $userId, $acNo, (string) $this->request->getPost('name')) : 0;

                    if ($role === 'teacher') {
                        $this->syncTeacherProfile((int) $userId, $model->find($userId));
                    }

                    $message = 'User created successfully.' . ($linked ? ' Linked ' . $linked . ' existing time record(s) from Biometric No. ' . $acNo . '.' : '');
                } elseif ($action === 'edit') {
                    $userId = (int) $this->request->getPost('id');
                    $role   = $this->request->getPost('role');

                    $model->update($userId, [
                        'name'  => $this->request->getPost('name'),
                        'email' => $this->request->getPost('email'),
                        'role'  => $role,
                        'ac_no' => $acNo !== '' ? $acNo : null,
                    ]);
                    // Past records stay with this person (they're tied by account, not number);
                    // a new number also picks up its not-yet-linked scans.
                    $linked = $acNo !== '' ? $this->linkBiometric($userId, $acNo, (string) $this->request->getPost('name')) : 0;

                    if ($role === 'teacher') {
                        $this->syncTeacherProfile($userId, $model->find($userId));
                    }

                    $message = 'User updated successfully.' . ($linked ? ' Linked ' . $linked . ' existing time record(s) from Biometric No. ' . $acNo . '.' : '');
                } elseif ($action === 'delete') {
                    if ((int) $this->request->getPost('id') === (int) currentUser()['id']) {
                        return $isAjax ? $this->ajaxError('You cannot delete your own account.') : redirect()->to('/users');
                    }
                    $model->delete((int) $this->request->getPost('id'));
                    $message = 'User deleted.';
                } elseif ($action === 'deactivate' || $action === 'reactivate') {
                    // e.g. the outgoing principal when a new one takes over —
                    // they can no longer sign in, but their records stay.
                    $userId = (int) $this->request->getPost('id');
                    if ($userId === (int) currentUser()['id']) {
                        return $isAjax ? $this->ajaxError('You cannot deactivate your own account.') : redirect()->to('/users');
                    }
                    $target = $model->find($userId);
                    if (! $target) {
                        return $isAjax ? $this->ajaxError('User not found.', 404) : redirect()->to('/users');
                    }

                    $activate = $action === 'reactivate';
                    $model->update($userId, [
                        'is_active'      => $activate ? 1 : 0,
                        'deactivated_at' => $activate ? null : date('Y-m-d H:i:s'),
                    ]);
                    $message = $target['name'] . ($activate ? ' can sign in again.' : ' has been deactivated and can no longer sign in.');
                } elseif ($action === 'reset_pw') {
                    $model->update((int) $this->request->getPost('id'), [
                        'password' => password_hash($this->request->getPost('new_password'), PASSWORD_BCRYPT),
                    ]);
                    $message = 'Password reset successfully.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/users');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/users');
        }

        $search     = trim($this->request->getGet('q') ?? '');
        $sort       = $this->request->getGet('sort') ?? 'role';
        $department = $this->request->getGet('dept') ?? 'all';
        $status     = $this->request->getGet('status') ?? 'all';

        $builder = $model->select('id,name,email,role,ac_no,is_active,deactivated_at,created_at')
            ->groupStart()
                ->like('name', $search)
                ->orLike('email', $search)
            ->groupEnd();

        if ($status === 'active' || $status === 'inactive') {
            $builder->where('is_active', $status === 'active' ? 1 : 0);
        }

        match ($sort) {
            'name_az' => $builder->orderBy('name', 'ASC'),
            'newest'  => $builder->orderBy('created_at', 'DESC'),
            'oldest'  => $builder->orderBy('created_at', 'ASC'),
            default   => $builder->orderBy('role', 'ASC')->orderBy('name', 'ASC'),
        };

        $users = $builder->findAll();

        // Attach each teacher-role user's advisory/grade/subjects so the Edit
        // modal can be pre-filled without a separate round trip.
        $teachersByUserId = [];
        foreach ((new TeacherModel())->allWithSubjects() as $teacher) {
            if ($teacher['user_id'] !== null) {
                $teachersByUserId[(int) $teacher['user_id']] = $teacher;
            }
        }
        $departmentsByUserId = (new TeacherSubjectModel())->departmentsByUserId();
        foreach ($users as &$u) {
            $u['teacher']     = $teachersByUserId[(int) $u['id']] ?? null;
            $u['departments'] = $departmentsByUserId[(int) $u['id']] ?? [];
        }
        unset($u);

        $departments = array_values(array_unique(array_merge([], ...array_values($departmentsByUserId))));
        sort($departments);

        if ($department !== 'all') {
            $users = array_values(array_filter($users, static fn ($u) => in_array($department, $u['departments'], true)));
        }

        // Scanner numbers seen in imports but not yet tied to an account — offered
        // as suggestions in the Biometric No. field.
        $unlinkedBiometrics = (new BiometricEmployeeModel())->select('ac_no, name')
            ->whereNotIn('ac_no', array_merge(['__none__'], array_filter(array_column($model->select('ac_no')->where('ac_no IS NOT NULL')->findAll(), 'ac_no'))))
            ->orderBy('CAST(ac_no AS UNSIGNED)', 'ASC', false)
            ->findAll();

        return view('pages/admin/users', [
            'unlinkedBiometrics' => $unlinkedBiometrics,
            'pageTitle'   => 'User Management',
            'users'       => $users,
            'search'      => $search,
            'sort'        => $sort,
            'department'  => $department,
            'departments' => $departments,
            'status'      => $status,
            'gradeLevels' => self::GRADE_LEVELS,
            'flash'       => session()->getFlashdata('flash'),
        ]);
    }

    /**
     * Creates or updates the teachers row tied to a user, and resyncs its
     * teacher_subjects rows, from the Add/Edit User form's teacher-only
     * fields (advisory_status, advisory_section, grade_level, subjects[]).
     */
    /**
     * Ties a biometric number to an account: its unclaimed time records move
     * to the account, and any "Unmapped (AC-x)" scanner placeholder for that
     * number is dropped (the account now names it).
     */
    private function linkBiometric(int $userId, string $acNo, string $name): int
    {
        (new BiometricEmployeeModel())->where('ac_no', $acNo)->delete();

        return (new TimeRecordModel())->claimForUser($userId, $acNo, $name);
    }

    private function syncTeacherProfile(int $userId, ?array $user): void
    {
        if (! $user) {
            return;
        }

        $teacherModel = new TeacherModel();
        $teacher      = $teacherModel->findByUserId($userId) ?? $teacherModel->findByEmail($user['email']);

        $advisoryStatus  = $this->request->getPost('advisory_status');
        $advisorySection = trim((string) $this->request->getPost('advisory_section'));
        $advisory        = null;
        if ($advisorySection !== '' && $advisoryStatus === 'adviser') {
            $advisory = $advisorySection;
        } elseif ($advisorySection !== '' && $advisoryStatus === 'co_adviser') {
            $advisory = $advisorySection . ' (Co-Adviser)';
        }

        $payload = [
            'name'        => $user['name'],
            'email'       => $user['email'],
            'grade_level' => trim((string) $this->request->getPost('grade_level')) ?: null,
            'advisory'    => $advisory,
            'user_id'     => $userId,
        ];

        if ($teacher) {
            $teacherModel->update($teacher['id'], $payload);
            $teacherId = (int) $teacher['id'];
        } else {
            $payload['employee_id']     = 'T-' . str_pad((string) $userId, 3, '0', STR_PAD_LEFT);
            $payload['submission_rate'] = 0.00;
            $teacherId                  = (int) $teacherModel->insert($payload);
        }

        $subjectModel = new TeacherSubjectModel();
        // Only the base load — per-term loads the teacher entered stay put.
        $subjectModel->where('teacher_id', $teacherId)->where('school_year', null)->delete();

        $subjects = $this->request->getPost('subjects') ?? [];
        foreach ($subjects as $row) {
            $subject = trim((string) ($row['subject'] ?? ''));
            $grade   = trim((string) ($row['grade'] ?? ''));
            $section = trim((string) ($row['section'] ?? ''));
            if ($subject === '' || $grade === '' || $section === '') {
                continue;
            }

            $subjectModel->insert([
                'teacher_id'  => $teacherId,
                'subject'     => $subject,
                'grade_level' => $grade,
                'section'     => $section,
            ]);
        }
    }
}
