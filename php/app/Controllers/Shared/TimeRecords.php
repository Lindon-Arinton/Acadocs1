<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\TimeRecordImporter;
use App\Models\HolidayModel;
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
                } elseif ($action === 'holiday_add') {
                    (new HolidayModel())->insert([
                        'date'  => $this->request->getPost('holiday_date'),
                        'label' => $this->request->getPost('holiday_label') ?: null,
                    ]);
                    $message = 'Holiday added.';
                } elseif ($action === 'holiday_delete') {
                    (new HolidayModel())->delete((int) $this->request->getPost('holiday_id'));
                    $message = 'Holiday removed.';
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
        $search     = trim($this->request->getGet('q') ?? '');
        $sort       = $this->request->getGet('sort') ?? 'name_az';

        $builder = $model->where('date', $dateFilter)
            ->where('employee_name NOT LIKE', 'Unmapped (%');

        if ($search !== '') {
            $builder->groupStart()
                ->like('employee_name', $search)
                ->orLike('employee_id', $search)
                ->orLike('remarks', $search)
                ->groupEnd();
        }

        match ($sort) {
            'status'  => $builder->orderBy('status', 'ASC')->orderBy('employee_name', 'ASC'),
            'time_in' => $builder->orderBy('time_in', 'ASC'),
            default   => $builder->orderBy('employee_name', 'ASC'),
        };

        $records = $builder->findAll();

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
            'search'     => $search,
            'sort'       => $sort,
            'flash'      => session()->getFlashdata('flash'),
            'holidays'   => (new HolidayModel())->orderBy('date', 'ASC')->findAll(),
        ]);
    }

    public function import()
    {
        $isAjax   = $this->request->isAJAX();
        $redirect = '/time-records';

        if (! hasRole('admin', 'adas')) {
            return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to($redirect);
        }

        $file = $this->request->getFile('import_file');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $isAjax ? $this->ajaxError('Please choose a valid Excel file to upload.') : redirect()->to($redirect);
        }

        if (! in_array(strtolower($file->getExtension() ?: ''), ['xlsx', 'xls'], true)) {
            return $isAjax ? $this->ajaxError('Only .xlsx or .xls files are supported.') : redirect()->to($redirect);
        }

        $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'time_records_imports';
        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $fileName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientName());

        if (! $file->move($uploadPath, $fileName)) {
            return $isAjax ? $this->ajaxError('Could not save the uploaded file.') : redirect()->to($redirect);
        }

        try {
            $summary = (new TimeRecordImporter())->import($uploadPath . DIRECTORY_SEPARATOR . $fileName);
        } catch (\Throwable $e) {
            return $isAjax ? $this->ajaxError('Import failed: ' . $e->getMessage()) : redirect()->to($redirect);
        }

        $message = sprintf(
            'Imported %d row(s): %d present, %d late, %d absent (%d incomplete). %d row(s) skipped (non-school day).',
            $summary['inserted'] + $summary['updated'],
            $summary['present'],
            $summary['late'],
            $summary['absent'],
            $summary['incomplete'],
            $summary['skipped_non_school_day'],
        );

        if ($summary['placeholders_created'] !== []) {
            $message .= ' Unmapped AC-No (no name in scanner) recorded as placeholders: ' . implode(', ', $summary['placeholders_created']) . '.';
        }

        if ($summary['errors'] !== []) {
            $message .= ' ' . count($summary['errors']) . ' row(s) had errors and were skipped.';
        }

        if ($summary['latest_date']) {
            $redirect = '/time-records?date=' . $summary['latest_date'];
            $message .= ' Showing ' . date('M d, Y', strtotime($summary['latest_date'])) . '.';
        }

        if ($isAjax) {
            return $this->ajaxSuccess($message, ['summary' => $summary, 'redirect' => $redirect]);
        }

        session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message]);

        return redirect()->to($redirect);
    }
}
