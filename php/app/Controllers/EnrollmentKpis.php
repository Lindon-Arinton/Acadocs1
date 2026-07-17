<?php

namespace App\Controllers;

use App\Models\EnrollmentByLevelModel;
use App\Models\KpiSnapshotModel;

class EnrollmentKpis extends BaseController
{
    public function index()
    {
        $year = $this->request->getGet('year') ?? '2025-2026';

        $kpi        = (new KpiSnapshotModel())->forYear($year);
        $enrollment = (new EnrollmentByLevelModel())->where('school_year', $year)->orderBy('grade_level')->findAll();
        $total      = array_sum(array_column($enrollment, 'students'));

        return view('pages/enrollment_kpis', [
            'pageTitle'  => 'Enrollment KPIs',
            'year'       => $year,
            'kpi'        => $kpi,
            'enrollment' => $enrollment,
            'total'      => $total,
        ]);
    }
}
