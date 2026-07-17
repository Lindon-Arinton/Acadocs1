<?php

namespace App\Controllers\Api;

use App\Models\RoomPropertyModel;

class PropertiesController extends BaseApiController
{
    public function index()
    {
        $model    = new RoomPropertyModel();
        $building = $this->request->getGet('building');
        $room     = $this->request->getGet('room');

        $builder = $model->orderBy('building_name')->orderBy('room_number')->orderBy('item_name');
        if ($building) {
            $builder->where('building_name', $building);
        }
        if ($room) {
            $builder->where('room_number', $room);
        }

        return $this->jsonResponse($builder->findAll());
    }

    public function create()
    {
        $b = $this->body();

        $id = (new RoomPropertyModel())->insert([
            'room_number'      => $b['room_number'] ?? '',
            'building_name'    => $b['building_name'] ?? '',
            'item_name'        => $b['item_name'] ?? '',
            'quantity'         => $b['quantity'] ?? 1,
            'condition_status' => $b['condition_status'] ?? 'Good',
            'last_inspection'  => $b['last_inspection'] ?? date('Y-m-d'),
            'remarks'          => $b['remarks'] ?? '',
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
            'quantity'         => $b['quantity'],
            'condition_status' => $b['condition_status'],
            'last_inspection'  => $b['last_inspection'],
            'remarks'          => $b['remarks'] ?? '',
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
