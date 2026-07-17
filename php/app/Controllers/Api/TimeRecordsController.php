<?php

namespace App\Controllers\Api;

use App\Models\TimeRecordModel;

class TimeRecordsController extends BaseApiController
{
    public function index()
    {
        $model = new TimeRecordModel();
        $date  = $this->request->getGet('date');

        $rows = $date
            ? $model->where('date', $date)->orderBy('employee_name')->findAll()
            : $model->orderBy('date', 'DESC')->orderBy('employee_name')->findAll();

        $summary = ['present' => 0, 'late' => 0, 'absent' => 0, 'on_leave' => 0];
        foreach ($rows as $r) {
            match ($r['status']) {
                'Present'  => $summary['present']++,
                'Late'     => $summary['late']++,
                'Absent'   => $summary['absent']++,
                'On Leave' => $summary['on_leave']++,
                default    => null,
            };
        }

        return $this->jsonResponse(['records' => $rows, 'summary' => $summary]);
    }

    public function create()
    {
        $b = $this->body();

        $id = (new TimeRecordModel())->insert([
            'date'          => $b['date'] ?? date('Y-m-d'),
            'employee_name' => $b['employee_name'] ?? '',
            'employee_id'   => $b['employee_id'] ?? '',
            'time_in'       => $b['time_in'] ?? null,
            'time_out'      => $b['time_out'] ?? null,
            'status'        => $b['status'] ?? 'Present',
            'remarks'       => $b['remarks'] ?? '',
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
        (new TimeRecordModel())->update($id, [
            'time_in'  => $b['time_in'],
            'time_out' => $b['time_out'],
            'status'   => $b['status'],
            'remarks'  => $b['remarks'] ?? '',
        ]);

        return $this->jsonResponse(['message' => 'Updated.']);
    }

    public function delete()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        (new TimeRecordModel())->delete($id);

        return $this->jsonResponse(['message' => 'Deleted.']);
    }
}
