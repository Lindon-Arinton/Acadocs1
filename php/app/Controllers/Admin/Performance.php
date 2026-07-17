<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\KpiSnapshotModel;
use App\Models\PerformanceByLevelModel;
use App\Models\PerformanceBySubjectModel;

class Performance extends BaseController
{
    public function index()
    {
        $year = $this->request->getGet('year') ?? '2025-2026';

        $byLevel   = (new PerformanceByLevelModel())->where('school_year', $year)->orderBy('grade_level')->findAll();
        $bySubject = (new PerformanceBySubjectModel())->where('school_year', $year)->orderBy('mps', 'DESC')->findAll();
        $kpi       = (new KpiSnapshotModel())->forYear($year);

        return view('pages/admin/performance', [
            'pageTitle' => 'Performance Analytics',
            'year'      => $year,
            'byLevel'   => $byLevel,
            'bySubject' => $bySubject,
            'kpi'       => $kpi,
        ]);
    }
}
