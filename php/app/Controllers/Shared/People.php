<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\TeacherModel;
use App\Models\TeacherSubjectModel;
use App\Models\UserModel;

/**
 * Person details for the shared "person card" modal (layout/footer.php),
 * opened by clicking any name rendered through personLink(): role, email,
 * position and — for teachers — advisory plus the subjects and sections
 * they handle (their most recent subject load).
 */
class People extends BaseController
{
    public function show(int $id)
    {
        $user = (new UserModel())->find($id);
        if (! $user) {
            return $this->ajaxError('Person not found.', 404);
        }

        $advisory = '';
        $subjects = [];
        $sections = [];

        if ($user['role'] === 'teacher') {
            // Look the teacher row up without TeacherModel::resolveForUser(),
            // which creates a row when none exists — this endpoint only reads.
            $teacherModel = new TeacherModel();
            $teacher      = $teacherModel->findByUserId((int) $user['id'])
                ?? ($user['email'] ? $teacherModel->findByEmail($user['email']) : null);

            if ($teacher) {
                $advisory = (string) ($teacher['advisory'] ?? '');

                foreach ((new TeacherSubjectModel())->forTeacher((int) $teacher['id']) as $row) {
                    $grade      = trim((string) ($row['grade_level'] ?? ''));
                    $subjects[] = trim($row['subject'] . ($grade !== '' ? ' — ' . $grade : ''));
                    if (! empty($row['section'])) {
                        $sections[] = ($grade !== '' ? $grade . ' – ' : '') . $row['section'];
                    }
                }
            }
        }

        $photo = ! empty($user['photo']) && is_file(FCPATH . 'uploads/avatars/' . $user['photo'])
            ? base_url('uploads/avatars/' . $user['photo'])
            : null;

        return $this->response->setJSON([
            'status' => 'success',
            'person' => [
                'id'       => (int) $user['id'],
                'name'     => $user['name'],
                'role'     => $user['role'],
                'position' => (string) ($user['position'] ?? ''),
                'email'    => (string) $user['email'],
                'photo'    => $photo,
                'advisory' => $advisory,
                'subjects' => array_values(array_unique($subjects)),
                'sections' => array_values(array_unique($sections)),
                'isSelf'   => (int) $user['id'] === (int) (currentUser()['id'] ?? 0),
                'chatUrl'  => base_url('chat?user=' . (int) $user['id']),
            ],
        ]);
    }
}
