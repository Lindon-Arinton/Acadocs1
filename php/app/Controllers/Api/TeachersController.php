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
            'advisory'        => $b['advisory'] ?? null,
            'submission_rate' => $b['submission_rate'] ?? 0,
        ]);

        $subjectModel = new TeacherSubjectModel();
        foreach (($b['subjects'] ?? []) as $subject) {
            $subjectModel->insert(array_merge(['teacher_id' => $teacherId], $this->normalizeSubject($subject)));
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
            'grade_level'     => $b['grade_level'] ?? null,
            'advisory'        => $b['advisory'] ?? null,
            'submission_rate' => $b['submission_rate'],
        ]);

        $subjectModel = new TeacherSubjectModel();
        $subjectModel->where('teacher_id', $id)->where('school_year', null)->delete();
        foreach (($b['subjects'] ?? []) as $subject) {
            $subjectModel->insert(array_merge(['teacher_id' => $id], $this->normalizeSubject($subject)));
        }

        return $this->jsonResponse(['message' => 'Updated.']);
    }

    /**
     * Accepts either a structured subject (['subject'=>,'grade_level'=>,'section'=>])
     * or a legacy flat string (e.g. "MAPEH 9 (5)") for backward compatibility with
     * older API clients that don't yet send grade/section — see PerformanceMps::
     * handledCells(), which already falls back to parsing that flat form.
     *
     * @param array<string,mixed>|string $subject
     * @return array{subject:string,grade_level:?string,section:?string}
     */
    private function normalizeSubject($subject): array
    {
        if (is_array($subject)) {
            return [
                'subject'     => (string) ($subject['subject'] ?? ''),
                'grade_level' => $subject['grade_level'] ?? null,
                'section'     => $subject['section'] ?? null,
            ];
        }

        return ['subject' => (string) $subject, 'grade_level' => null, 'section' => null];
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
