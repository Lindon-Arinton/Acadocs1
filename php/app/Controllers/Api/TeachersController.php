<?php

namespace App\Controllers\Api;

use App\Models\TeacherModel;
use App\Models\TeacherSubjectModel;

class TeachersController extends BaseApiController
{
    public function index()
    {
        $model = new TeacherModel();
        $id    = $this->request->getGet('id');

        if ($id) {
            $row = $model->findWithSubjects((int) $id);

            return $row ? $this->jsonResponse($row) : $this->jsonError('Not found.', 404);
        }

        return $this->jsonResponse($model->allWithSubjects());
    }

    public function create()
    {
        $b = $this->body();

        $teacherId = (new TeacherModel())->insert([
            'employee_id'     => $b['employee_id'] ?? '',
            'name'            => $b['name'] ?? '',
            'email'           => $b['email'] ?? '',
            'grade_level'     => $b['grade_level'] ?? '',
            'submission_rate' => $b['submission_rate'] ?? 0,
        ]);

        $subjectModel = new TeacherSubjectModel();
        foreach (($b['subjects'] ?? []) as $subject) {
            $subjectModel->insert(['teacher_id' => $teacherId, 'subject' => $subject]);
        }

        return $this->jsonResponse(['id' => $teacherId, 'message' => 'Created.'], 201);
    }

    public function update()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        $b = $this->body();
        (new TeacherModel())->update($id, [
            'employee_id'     => $b['employee_id'],
            'name'            => $b['name'],
            'email'           => $b['email'],
            'grade_level'     => $b['grade_level'],
            'submission_rate' => $b['submission_rate'],
        ]);

        $subjectModel = new TeacherSubjectModel();
        $subjectModel->where('teacher_id', $id)->delete();
        foreach (($b['subjects'] ?? []) as $subject) {
            $subjectModel->insert(['teacher_id' => $id, 'subject' => $subject]);
        }

        return $this->jsonResponse(['message' => 'Updated.']);
    }

    public function delete()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        (new TeacherModel())->delete($id);

        return $this->jsonResponse(['message' => 'Deleted.']);
    }
}
