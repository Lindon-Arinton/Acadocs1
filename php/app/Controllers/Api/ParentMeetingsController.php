<?php

namespace App\Controllers\Api;

use App\Models\ParentMeetingModel;

class ParentMeetingsController extends BaseApiController
{
    public function index()
    {
        return $this->jsonResponse((new ParentMeetingModel())->orderBy('date', 'DESC')->findAll());
    }

    public function create()
    {
        $b        = $this->body();
        $actual   = (int) ($b['actual_attendance'] ?? 0);
        $expected = (int) ($b['expected_parents'] ?? 1);
        $rate     = $expected > 0 ? round($actual / $expected * 100, 2) : 0;

        $id = (new ParentMeetingModel())->insert([
            'title'             => $b['title'],
            'date'              => $b['date'],
            'expected_parents'  => $expected,
            'actual_attendance' => $actual,
            'attendance_rate'   => $rate,
        ]);

        return $this->jsonResponse(['id' => $id, 'attendance_rate' => $rate, 'message' => 'Created.'], 201);
    }

    public function update()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        $b        = $this->body();
        $actual   = (int) ($b['actual_attendance'] ?? 0);
        $expected = (int) ($b['expected_parents'] ?? 1);
        $rate     = $expected > 0 ? round($actual / $expected * 100, 2) : 0;

        (new ParentMeetingModel())->update($id, [
            'title'             => $b['title'],
            'date'              => $b['date'],
            'expected_parents'  => $expected,
            'actual_attendance' => $actual,
            'attendance_rate'   => $rate,
        ]);

        return $this->jsonResponse(['message' => 'Updated.']);
    }

    public function delete()
    {
        $id = (int) $this->request->getGet('id');
        if (! $id) {
            return $this->jsonError('Method not allowed.', 405);
        }

        (new ParentMeetingModel())->delete($id);

        return $this->jsonResponse(['message' => 'Deleted.']);
    }
}
