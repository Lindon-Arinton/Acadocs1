<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\RoomPropertyModel;

class Properties extends BaseController
{
    public function index()
    {
        $model = new RoomPropertyModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('admin')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/property-management');
            }

            $action  = $this->request->getPost('action');
            $message = null;

            try {
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
                    $message = 'Item added successfully.';
                } elseif ($action === 'delete') {
                    $model->delete((int) $this->request->getPost('id'));
                    $message = 'Item removed.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/property-management');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/property-management');
        }

        $building  = $this->request->getGet('building') ?? 'all';
        $condition = $this->request->getGet('condition') ?? 'all';
        $search    = trim($this->request->getGet('q') ?? '');
        $sort      = $this->request->getGet('sort') ?? 'building_az';

        $builder = $model;
        if ($building !== 'all') {
            $builder->where('building_name', $building);
        }
        if ($condition !== 'all') {
            $builder->where('condition_status', $condition);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('room_number', $search)
                ->orLike('item_name', $search)
                ->orLike('remarks', $search)
                ->groupEnd();
        }

        match ($sort) {
            'item_az'   => $builder->orderBy('item_name', 'ASC'),
            'condition' => $builder->orderBy('condition_status', 'ASC')->orderBy('item_name', 'ASC'),
            'qty_desc'  => $builder->orderBy('quantity', 'DESC'),
            'inspected' => $builder->orderBy('last_inspection', 'DESC'),
            default     => $builder->orderBy('building_name', 'ASC')->orderBy('room_number', 'ASC')->orderBy('item_name', 'ASC'),
        };

        $items = $builder->findAll();

        $buildings  = array_column((new RoomPropertyModel())->distinct()->select('building_name')->orderBy('building_name')->findAll(), 'building_name');
        $conditions = ['Excellent', 'Good', 'Fair', 'Poor'];

        $condStats  = array_count_values(array_column($items, 'condition_status'));
        $totalItems = array_sum(array_column($items, 'quantity'));

        return view('pages/admin/properties', [
            'pageTitle'  => 'Property Management',
            'items'      => $items,
            'building'   => $building,
            'condition'  => $condition,
            'search'     => $search,
            'sort'       => $sort,
            'buildings'  => $buildings,
            'conditions' => $conditions,
            'condStats'  => $condStats,
            'totalItems' => $totalItems,
            'flash'      => session()->getFlashdata('flash'),
        ]);
    }
}
