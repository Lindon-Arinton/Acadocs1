<?php

namespace App\Controllers\Api;

use App\Models\DepedDocumentModel;

class DepedDocumentsController extends BaseApiController
{
    public function index()
    {
        $model = new DepedDocumentModel();
        $id    = $this->request->getGet('id');

        if ($id) {
            $row = $model->find((int) $id);

            return $row ? $this->jsonResponse($row) : $this->jsonError('Not found.', 404);
        }

        return $this->jsonResponse($model->orderBy('due_date')->findAll());
    }

    public function create()
    {
        $b = $this->body();

        $id = (new DepedDocumentModel())->insert([
            'document_type'   => $b['document_type'] ?? '',
            'description'     => $b['description'] ?? '',
            'due_date'        => $b['due_date'] ?? date('Y-m-d'),
            'status'          => $b['status'] ?? 'Pending',
            'completion_rate' => $b['completion_rate'] ?? 0,
            'prepared_by'     => $b['prepared_by'] ?? '',
            'last_updated'    => $b['last_updated'] ?? date('Y-m-d'),
        ]);

        return $this->jsonResponse(['id' => $id, 'message' => 'Created.'], 201);
    }

    public function update()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        $b = $this->body();
        (new DepedDocumentModel())->update($id, [
            'status'          => $b['status'],
            'completion_rate' => $b['completion_rate'],
            'last_updated'    => date('Y-m-d'),
        ]);

        return $this->jsonResponse(['message' => 'Updated.']);
    }

    public function delete()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        (new DepedDocumentModel())->delete($id);

        return $this->jsonResponse(['message' => 'Deleted.']);
    }
}
