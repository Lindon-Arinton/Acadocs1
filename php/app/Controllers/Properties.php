<?php

namespace App\Controllers;

use App\Models\RoomPropertyModel;

class Properties extends BaseController
{
    public function index()
    {
        $model = new RoomPropertyModel();

        if ($this->request->getMethod() === 'POST' && hasRole('admin')) {
            $action = $this->request->getPost('action');

            if ($action === 'add') {
                $model->insert([
                    'room_number'      => $this->request->getPost('room_number'),
                    'building_name'    => $this->request->getPost('building_name'),
                    'item_name'        => $this->request->getPost('item_name'),
                    'quantity'         => (int) $this->request->getPost('quantity'),
                    'condition_status' => $this->request->getPost('condition_status'),
                    'last_inspection'  => $this->request->getPost('last_inspection') ?: date('Y-m-d'),
                    'remarks'          => $this->request->getPost('remarks') ?? '',
                ]);
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Item added successfully.']);
            } elseif ($action === 'delete') {
                $model->delete((int) $this->request->getPost('id'));
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Item removed.']);
            }

            return redirect()->to('/property-management');
        }

        $building  = $this->request->getGet('building') ?? 'all';
        $condition = $this->request->getGet('condition') ?? 'all';

        $builder = $model->orderBy('building_name')->orderBy('room_number')->orderBy('item_name');
        if ($building !== 'all') {
            $builder->where('building_name', $building);
        }
        if ($condition !== 'all') {
            $builder->where('condition_status', $condition);
        }
        $items = $builder->findAll();

        $buildings  = array_column((new RoomPropertyModel())->distinct()->select('building_name')->orderBy('building_name')->findAll(), 'building_name');
        $conditions = ['Excellent', 'Good', 'Fair', 'Poor'];

        $condStats  = array_count_values(array_column($items, 'condition_status'));
        $totalItems = array_sum(array_column($items, 'quantity'));

        return view('pages/properties', [
            'pageTitle'  => 'Property Management',
            'items'      => $items,
            'building'   => $building,
            'condition'  => $condition,
            'buildings'  => $buildings,
            'conditions' => $conditions,
            'condStats'  => $condStats,
            'totalItems' => $totalItems,
            'flash'      => session()->getFlashdata('flash'),
        ]);
    }
}
