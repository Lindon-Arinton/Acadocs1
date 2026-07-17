<?php

namespace App\Controllers;

use App\Models\TimeRecordModel;

class TimeRecords extends BaseController
{
    public function index()
    {
        $model = new TimeRecordModel();

        if ($this->request->getMethod() === 'POST' && hasRole('admin', 'adas')) {
            $action = $this->request->getPost('action');

            if ($action === 'add') {
                $model->insert([
                    'date'          => $this->request->getPost('date'),
                    'employee_name' => $this->request->getPost('employee_name'),
                    'employee_id'   => $this->request->getPost('employee_id'),
                    'time_in'       => $this->request->getPost('time_in') ?: null,
                    'time_out'      => $this->request->getPost('time_out') ?: null,
                    'status'        => $this->request->getPost('status'),
                    'remarks'       => $this->request->getPost('remarks') ?? '',
                ]);
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Time record added.']);
            } elseif ($action === 'update') {
                $model->update((int) $this->request->getPost('id'), [
                    'time_in'  => $this->request->getPost('time_in') ?: null,
                    'time_out' => $this->request->getPost('time_out') ?: null,
                    'status'   => $this->request->getPost('status'),
                    'remarks'  => $this->request->getPost('remarks') ?? '',
                ]);
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Record updated.']);
            }

            return redirect()->to('/time-records?date=' . ($this->request->getPost('date') ?? date('Y-m-d')));
        }

        $dateFilter = $this->request->getGet('date') ?? date('Y-m-d');
        $records    = $model->where('date', $dateFilter)->orderBy('employee_name')->findAll();

        $summary = ['Present' => 0, 'Late' => 0, 'Absent' => 0, 'On Leave' => 0];
        foreach ($records as $r) {
            if (isset($summary[$r['status']])) {
                $summary[$r['status']]++;
            }
        }

        return view('pages/time_records', [
            'pageTitle'  => 'Time Records',
            'records'    => $records,
            'summary'    => $summary,
            'dateFilter' => $dateFilter,
            'flash'      => session()->getFlashdata('flash'),
        ]);
    }
}
