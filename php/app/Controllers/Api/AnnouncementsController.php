<?php

namespace App\Controllers\Api;

use App\Models\AnnouncementModel;

class AnnouncementsController extends BaseApiController
{
    public function index()
    {
        $model = new AnnouncementModel();
        $id    = $this->request->getGet('id');

        if ($id) {
            $row = $model->find((int) $id);

            return $row ? $this->jsonResponse($row) : $this->jsonError('Not found.', 404);
        }

        return $this->jsonResponse($model->orderBy('date', 'DESC')->findAll());
    }

    public function create()
    {
        $b     = $this->body();
        $model = new AnnouncementModel();

        $id = $model->insert([
            'type'    => $b['type'] ?? 'Announcement',
            'title'   => $b['title'] ?? '',
            'content' => $b['content'] ?? '',
            'date'    => $b['date'] ?? date('Y-m-d'),
            'status'  => $b['status'] ?? 'active',
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
        (new AnnouncementModel())->update($id, [
            'type'    => $b['type'],
            'title'   => $b['title'],
            'content' => $b['content'],
            'date'    => $b['date'],
            'status'  => $b['status'],
        ]);

        return $this->jsonResponse(['message' => 'Updated.']);
    }

    public function delete()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        (new AnnouncementModel())->delete($id);

        return $this->jsonResponse(['message' => 'Deleted.']);
    }
}
