<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\TimeRecordImporter;
use App\Models\AcademicBreakModel;
use App\Models\HolidayModel;
use App\Models\TimeRecordModel;
use App\Models\UserModel;

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
                if ($action === 'update') {
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
                } elseif ($action === 'break_add') {
                    $label = trim((string) $this->request->getPost('break_label'));
                    $start = (string) $this->request->getPost('break_start');
                    $end   = (string) $this->request->getPost('break_end');
                    if ($label === '' || ! strtotime($start) || ! strtotime($end) || $end < $start) {
                        return $isAjax ? $this->ajaxError('Enter a name and a start date on or before the end date.') : redirect()->to($redirect);
                    }

                    (new AcademicBreakModel())->insert(['label' => $label, 'start_date' => $start, 'end_date' => $end]);
                    // Imported "no punches" absences inside the break weren't absences after all.
                    $model->set(['status' => 'Academic Break', 'remarks' => TimeRecordModel::BREAK_REMARK . $label])
                        ->where('date >=', $start)->where('date <=', $end)
                        ->where('status', 'Absent')->where('remarks', TimeRecordModel::NO_PUNCH_REMARK)
                        ->update();
                    $changed = db_connect()->affectedRows();
                    $message = 'Academic break added.' . ($changed ? ' ' . $changed . ' absence' . ($changed === 1 ? '' : 's') . ' in that range changed to Academic Break.' : '');
                } elseif ($action === 'break_delete') {
                    $breaks = new AcademicBreakModel();
                    $break  = $breaks->find((int) $this->request->getPost('break_id'));
                    if ($break) {
                        $breaks->delete($break['id']);
                        // Undo only what adding the break changed (records an admin set by hand stay put).
                        $model->set(['status' => 'Absent', 'remarks' => TimeRecordModel::NO_PUNCH_REMARK])
                            ->where('date >=', $break['start_date'])->where('date <=', $break['end_date'])
                            ->where('status', 'Academic Break')->where('remarks', TimeRecordModel::BREAK_REMARK . $break['label'])
                            ->update();
                    }
                    $message = 'Academic break removed.';
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

        $dateFilter   = $this->request->getGet('date') ?? date('Y-m-d');
        $search       = trim($this->request->getGet('q') ?? '');
        $sort         = $this->request->getGet('sort') ?? 'name_az';
        $statusFilter = $this->request->getGet('status') ?? 'all';

        if ($statusFilter !== 'all' && ! in_array($statusFilter, TimeRecordModel::STATUSES, true)) {
            $statusFilter = 'all';
        }

        // Summary counts always reflect every status for the date (still
        // respecting search), so every card stays visible/clickable no
        // matter which status is currently selected in the filter.
        $model->where('date', $dateFilter)->where('employee_name NOT LIKE', 'Unmapped (%');
        if ($search !== '') {
            $model->groupStart()
                ->like('employee_name', $search)
                ->orLike('employee_id', $search)
                ->orLike('remarks', $search)
                ->groupEnd();
        }
        $summaryRows = $model->findAll();

        $summary = array_fill_keys(TimeRecordModel::STATUSES, 0);
        foreach ($summaryRows as $r) {
            if (isset($summary[$r['status']])) {
                $summary[$r['status']]++;
            }
        }

        $builder = $model->where('date', $dateFilter)
            ->where('employee_name NOT LIKE', 'Unmapped (%');

        if ($search !== '') {
            $builder->groupStart()
                ->like('employee_name', $search)
                ->orLike('employee_id', $search)
                ->orLike('remarks', $search)
                ->groupEnd();
        }

        if ($statusFilter !== 'all') {
            $builder->where('status', $statusFilter);
        }

        match ($sort) {
            'status'  => $builder->orderBy('status', 'ASC')->orderBy('employee_name', 'ASC'),
            'time_in' => $builder->orderBy('time_in', 'ASC'),
            default   => $builder->orderBy('employee_name', 'ASC'),
        };

        $records = $builder->findAll();

        // Each record carries its owner's account (time_records.user_id) — map it so
        // the name can open the person card (personLink()); unlinked scans stay plain.
        $userIdsByEmployeeId = [];
        foreach ($records as $r) {
            if (! empty($r['user_id'])) {
                $userIdsByEmployeeId[$r['employee_id']] = (int) $r['user_id'];
            }
        }

        return view('pages/shared/time_records', [
            'userIdsByEmployeeId' => $userIdsByEmployeeId,
            'pageTitle'    => 'Time Records',
            'records'      => $records,
            'summary'      => $summary,
            'dateFilter'   => $dateFilter,
            'search'       => $search,
            'sort'         => $sort,
            'statusFilter' => $statusFilter,
            'flash'        => session()->getFlashdata('flash'),
            'holidays'     => (new HolidayModel())->orderBy('date', 'ASC')->findAll(),
            'academicBreaks' => (new AcademicBreakModel())->orderBy('start_date', 'ASC')->findAll(),
            'activeBreak'    => (new AcademicBreakModel())->covering($dateFilter),
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

        // Use the client's extension: biometric .xls exports are often raw
        // BIFF streams whose MIME sniffs as octet-stream, so getExtension()
        // guesses "bin". The importer validates the actual content.
        if (! in_array(strtolower($file->getClientExtension()), ['xlsx', 'xls'], true)) {
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
            'Imported %d attendance record(s) from %d row(s): %d present, %d late, %d absent (%d incomplete).',
            $summary['inserted'] + $summary['updated'],
            $summary['rows_read'],
            $summary['present'],
            $summary['late'],
            $summary['absent'],
            $summary['incomplete'],
        );

        if ($summary['academic_break'] > 0) {
            $message .= ' ' . $summary['academic_break'] . ' day(s) recorded as Academic Break.';
        }

        if ($summary['skipped_non_school_day'] > 0) {
            $message .= ' ' . $summary['skipped_non_school_day'] . ' row(s) skipped (non-school day).';
        }

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
