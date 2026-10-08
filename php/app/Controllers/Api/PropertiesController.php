<?php

namespace App\Controllers\Api;

use App\Models\PropertyConditionLogModel;
use App\Models\RoomPropertyModel;
use CodeIgniter\Database\RawSql;

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
            'quantity'         => (int) ($b['quantity'] ?? 1),
            'condition_status' => in_array($b['condition_status'] ?? '', RoomPropertyModel::CONDITIONS, true) ? $b['condition_status'] : 'Serviceable',
            'acquisition_type' => in_array($b['acquisition_type'] ?? '', RoomPropertyModel::ACQUISITIONS, true) ? $b['acquisition_type'] : 'New',
            'notes'            => $b['notes'] ?? null,
            'uploaded_by'      => $b['uploaded_by'] ?? (currentUser()['name'] ?? null),
        ]);
        (new PropertyConditionLogModel())->record((int) $id, null, $b['condition_status'] ?? 'Serviceable', 'Item added');

        return $this->jsonResponse(['id' => $id, 'message' => 'Created.'], 201);
    }

    public function update()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        $b     = $this->body();
        $model = new RoomPropertyModel();
        $item  = $model->find($id);
        $to    = $b['condition_status'] ?? '';
        if (! $item) {
            return $this->jsonError('Item not found.', 404);
        }
        if (! in_array($to, RoomPropertyModel::CONDITIONS, true)) {
            return $this->jsonError('condition_status must be Serviceable or Non-serviceable.', 422);
        }

        if ($to !== $item['condition_status']) {
            $model->update($id, ['condition_status' => $to, 'updated_at' => new RawSql('NOW()')]);
            (new PropertyConditionLogModel())->record($id, $item['condition_status'], $to, $b['remarks'] ?? null);
        }

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
