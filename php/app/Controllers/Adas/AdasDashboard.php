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

        return view('pages/adas/adas_dashboard', [
            'pageTitle'   => 'ADAS Dashboard',
            'user'        => $user,
            'dateFilter'  => $dateFilter,
            'timeSummary' => $timeSummary,
        ]);
    }
}
