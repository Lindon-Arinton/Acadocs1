<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\ParentMeetingModel;

class ParentMeetings extends BaseController
{
    public function index()
    {
        $model = new ParentMeetingModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('admin', 'secretary')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/parent-meetings');
            }

            $message = null;

            try {
                if ($this->request->getPost('action') === 'add') {
                    $date = $this->request->getPost('date');

                    if ($date < date('Y-m-d')) {
                        $error = 'Meeting date cannot be in the past.';

                        return $isAjax ? $this->ajaxError($error) : redirect()->to('/parent-meetings')->with('flash', ['type' => 'danger', 'msg' => $error]);
                    }

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
                    $message = 'Meeting record saved.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/parent-meetings');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            return redirect()->to('/parent-meetings?success=1');
        }

        $search = trim($this->request->getGet('q') ?? '');
        $sort   = $this->request->getGet('sort') ?? 'newest';

        $builder = $model;
        if ($search !== '') {
            $builder->like('title', $search);
        }

        match ($sort) {
            'oldest'        => $builder->orderBy('date', 'ASC'),
            'attendance_hi' => $builder->orderBy('attendance_rate', 'DESC'),
            'attendance_lo' => $builder->orderBy('attendance_rate', 'ASC'),
            default         => $builder->orderBy('date', 'DESC'),
        };

        $meetings = $builder->findAll();

        $chartMeetings = $meetings;
        usort($chartMeetings, static fn (array $a, array $b): int => strtotime($a['date']) <=> strtotime($b['date']));

        return view('pages/shared/parent_meetings', [
            'pageTitle'     => 'Parent Meetings',
            'meetings'      => $meetings,
            'chartMeetings' => $chartMeetings,
            'search'        => $search,
            'sort'          => $sort,
        ]);
    }
}
