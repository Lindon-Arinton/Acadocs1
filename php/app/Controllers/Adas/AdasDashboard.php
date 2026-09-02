<?php

namespace App\Controllers\Adas;

use App\Controllers\BaseController;
use App\Models\TimeRecordModel;

class AdasDashboard extends BaseController
{
    public function index()
    {
        $user = currentUser();

        $dateFilter = date('Y-m-d');
        $records    = (new TimeRecordModel())->where('date', $dateFilter)->orderBy('employee_name')->findAll();

        $timeSummary = ['Present' => 0, 'Late' => 0, 'Absent' => 0, 'On Leave' => 0];
        foreach ($records as $r) {
            if (isset($timeSummary[$r['status']])) {
                $timeSummary[$r['status']]++;
            }
        }

        $myAttendance       = ['Present' => 0, 'Absent' => 0];
        $myAttendanceMonths = [];
        $attMonth           = $this->request->getGet('att_month') ?: 'all';

        if (! empty($user['ac_no'])) {
            $trModel = new TimeRecordModel();

            $myAttendanceMonths = array_column(
                $trModel->select("DATE_FORMAT(date, '%Y-%m') as ym", false)
                    ->where('employee_id', 'AC-' . $user['ac_no'])
                    ->distinct()
                    ->orderBy('ym', 'DESC')
                    ->findAll(),
                'ym'
            );

            if ($attMonth !== 'all' && ! in_array($attMonth, $myAttendanceMonths, true)) {
                $attMonth = 'all';
            }

            $myQuery = $trModel->where('employee_id', 'AC-' . $user['ac_no']);
            if ($attMonth !== 'all') {
                $myQuery->where('date >=', $attMonth . '-01')
                    ->where('date <=', date('Y-m-t', strtotime($attMonth . '-01')));
            }

            foreach ($myQuery->findAll() as $r) {
                if (isset($myAttendance[$r['status']])) {
                    $myAttendance[$r['status']]++;
                }
            }
        }

        return view('pages/adas/adas_dashboard', [
            'pageTitle'          => 'ADAS Dashboard',
            'user'               => $user,
            'dateFilter'         => $dateFilter,
            'timeSummary'        => $timeSummary,
            'myAttendance'       => $myAttendance,
            'myAttendanceMonths' => $myAttendanceMonths,
            'attMonth'           => $attMonth,
        ]);
    }
}
