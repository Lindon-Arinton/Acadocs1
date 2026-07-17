<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\TimeRecordModel;

class TimeRecords extends BaseController
{
    public function index()
    {
        $model = new TimeRecordModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax   = $this->request->isAJAX();
            $redirect = '/time-records?date=' . ($this->request->getPost('date') ?? date('Y-m-d'));

            if (! hasRole('admin', 'adas')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to($redirect);
            }

            $action  = $this->request->getPost('action');
            $message = null;

            try {
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
                    $message = 'Time record added.';
                } elseif ($action === 'update') {
                    $model->update((int) $this->request->getPost('id'), [
                        'time_in'  => $this->request->getPost('time_in') ?: null,
                        'time_out' => $this->request->getPost('time_out') ?: null,
                        'status'   => $this->request->getPost('status'),
                        'remarks'  => $this->request->getPost('remarks') ?? '',
                    ]);
                    $message = 'Record updated.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to($redirect);
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to($redirect);
        }

        $dateFilter = $this->request->getGet('date') ?? date('Y-m-d');
        $records    = $model->where('date', $dateFilter)->orderBy('employee_name')->findAll();

        $summary = ['Present' => 0, 'Late' => 0, 'Absent' => 0, 'On Leave' => 0];
        foreach ($records as $r) {
            if (isset($summary[$r['status']])) {
                $summary[$r['status']]++;
            }
        }

        return view('pages/shared/time_records', [
            'pageTitle'  => 'Time Records',
            'records'    => $records,
            'summary'    => $summary,
            'dateFilter' => $dateFilter,
            'flash'      => session()->getFlashdata('flash'),
        ]);
    }
}
