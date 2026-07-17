<?php

namespace App\Controllers;

use App\Models\ParentMeetingModel;

class ParentMeetings extends BaseController
{
    public function index()
    {
        $model = new ParentMeetingModel();

        if ($this->request->getMethod() === 'POST' && hasRole('admin', 'secretary')) {
            if ($this->request->getPost('action') === 'add') {
                $actual   = (int) $this->request->getPost('actual_attendance');
                $expected = (int) $this->request->getPost('expected_parents');
                $rate     = $expected > 0 ? round($actual / $expected * 100, 2) : 0;

                $model->insert([
                    'title'             => $this->request->getPost('title'),
                    'date'              => $this->request->getPost('date'),
                    'expected_parents'  => $expected,
                    'actual_attendance' => $actual,
                    'attendance_rate'   => $rate,
                ]);
            }

            return redirect()->to('/parent-meetings?success=1');
        }

        return view('pages/parent_meetings', [
            'pageTitle' => 'Parent Meetings',
            'meetings'  => $model->orderBy('date', 'DESC')->findAll(),
        ]);
    }
}
