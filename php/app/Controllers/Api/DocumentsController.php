<?php

namespace App\Controllers\Api;

use App\Models\DocumentFeedbackModel;
use App\Models\DocumentModel;

class DocumentsController extends BaseApiController
{
    public function index()
    {
        $model = new DocumentModel();
        $id    = $this->request->getGet('id');

        if ($id) {
            $doc = $model->findWithTeacher((int) $id);
            if (! $doc) {
                return $this->jsonError('Not found.', 404);
            }

            $doc['feedback'] = (new DocumentFeedbackModel())->where('document_id', $id)->orderBy('date')->findAll();

            return $this->jsonResponse($doc);
        }

        $teacherId = $this->request->getGet('teacher_id');

        return $this->jsonResponse($model->allWithTeacher($teacherId ? (int) $teacherId : null));
    }

    public function create()
    {
        $b = $this->body();

        $id = (new DocumentModel())->insert([
            'teacher_id'     => $b['teacher_id'] ?? 0,
            'type'           => $b['type'] ?? 'DLL',
            'subject'        => $b['subject'] ?? '',
            'grade_level'    => $b['grade_level'] ?? '',
            'date_submitted' => $b['date_submitted'] ?? date('Y-m-d H:i:s'),
            'status'         => $b['status'] ?? 'Submitted',
        ]);

        return $this->jsonResponse(['id' => $id, 'message' => 'Created.'], 201);
    }

    public function addFeedback()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        $b = $this->body();

        $feedbackId = (new DocumentFeedbackModel())->insert([
            'document_id' => $id,
            'author'      => $b['author'] ?? '',
            'comment'     => $b['comment'] ?? '',
            'date'        => $b['date'] ?? date('Y-m-d'),
        ]);

        (new DocumentModel())->update($id, ['status' => 'Reviewed']);

        return $this->jsonResponse(['id' => $feedbackId, 'message' => 'Feedback added.'], 201);
    }

    public function update()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        $b = $this->body();
        (new DocumentModel())->update($id, [
            'type'        => $b['type'],
            'subject'     => $b['subject'],
            'grade_level' => $b['grade_level'],
            'status'      => $b['status'],
        ]);

        return $this->jsonResponse(['message' => 'Updated.']);
    }

    public function delete()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        (new DocumentModel())->delete($id);

        return $this->jsonResponse(['message' => 'Deleted.']);
    }
}
