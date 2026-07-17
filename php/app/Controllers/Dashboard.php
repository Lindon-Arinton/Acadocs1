<?php

namespace App\Controllers;

use App\Models\CanteenRecordModel;
use App\Models\DocumentModel;
use App\Models\EnrollmentByLevelModel;
use App\Models\KpiSnapshotModel;
use App\Models\PerformanceByLevelModel;
use App\Models\PerformanceBySubjectModel;
use App\Models\SchoolFundModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $kpi        = (new KpiSnapshotModel())->latest();
        $enrollment = (new EnrollmentByLevelModel())->where('school_year', '2025-2026')->orderBy('grade_level')->findAll();
        $perfLevel  = (new PerformanceByLevelModel())->where('school_year', '2025-2026')->orderBy('grade_level')->findAll();

        $perfSubjectModel = new PerformanceBySubjectModel();
        $perfSubjectAsc   = $perfSubjectModel->where('school_year', '2025-2026')->orderBy('mps', 'ASC')->findAll();
        $lowest           = $perfSubjectAsc[0] ?? null;
        $allPerf          = $perfSubjectModel->where('school_year', '2025-2026')->orderBy('mps', 'DESC')->findAll();

        $documentModel = new DocumentModel();
        $docSummary    = $documentModel->statusCounts();
        $recentDocs    = $documentModel->allWithTeacher(null, 5);

        $canteen     = (new CanteenRecordModel())->totals();
        $fundBalance = (new SchoolFundModel())->currentBalance();

        return view('pages/dashboard', [
            'pageTitle'   => 'Admin Dashboard',
            'kpi'         => $kpi,
            'enrollment'  => $enrollment,
            'perfLevel'   => $perfLevel,
            'lowest'      => $lowest,
            'allPerf'     => $allPerf,
            'docSummary'  => $docSummary,
            'recentDocs'  => $recentDocs,
            'canteen'     => $canteen,
            'fundBalance' => $fundBalance,
        ]);
    }
}
