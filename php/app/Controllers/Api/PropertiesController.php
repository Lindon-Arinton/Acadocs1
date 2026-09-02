<?php

namespace App\Controllers\Api;

use App\Models\RoomPropertyModel;

class PropertiesController extends BaseApiController
{
    public function index()
    {
        $model   = new RoomPropertyModel();
        $grade   = $this->request->getGet('grade');
        $section = $this->request->getGet('section');

        $builder = $model->orderBy('grade')->orderBy('section')->orderBy('item_name');
        if ($grade) {
            $builder->where('grade', $grade);
        }
        if ($section) {
            $builder->where('section', $section);
        }

        return $this->jsonResponse($builder->findAll());
    }

    public function create()
    {
        $b = $this->body();

        $id = (new RoomPropertyModel())->insert([
            'section'          => $b['section'] ?? '',
            'grade'            => $b['grade'] ?? '',
            'item_name'        => $b['item_name'] ?? '',
            'condition_status' => $b['condition_status'] ?? 'Good',
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
        (new RoomPropertyModel())->update($id, [
            'condition_status' => $b['condition_status'],
        ]);

        return $this->jsonResponse(['message' => 'Updated.']);
    }

    public function delete()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        (new RoomPropertyModel())->delete($id);

        return $this->jsonResponse(['message' => 'Deleted.']);
    }
}
