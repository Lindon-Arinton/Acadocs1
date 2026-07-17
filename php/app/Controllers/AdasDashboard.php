<?php

namespace App\Controllers;

use App\Models\DepedDocumentModel;
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

        $depedModel = new DepedDocumentModel();
        $depedDocs  = $depedModel->orderBy('due_date', 'ASC')->findAll(5);

        $depedStatusCount = ['Completed' => 0, 'In Progress' => 0, 'Pending' => 0];
        foreach ($depedModel->findAll() as $d) {
            if (isset($depedStatusCount[$d['status']])) {
                $depedStatusCount[$d['status']]++;
            }
        }

        return view('pages/adas_dashboard', [
            'pageTitle'        => 'ADAS Dashboard',
            'user'             => $user,
            'dateFilter'       => $dateFilter,
            'timeSummary'      => $timeSummary,
            'depedDocs'        => $depedDocs,
            'depedStatusCount' => $depedStatusCount,
        ]);
    }
}
