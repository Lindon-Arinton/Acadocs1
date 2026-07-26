<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\EnrollmentByLevelModel;
use App\Models\KpiSnapshotModel;
use App\Models\PerformanceByLevelModel;
use App\Models\PerformanceBySubjectModel;

class Dashboard extends BaseController
{
    // Hardcoded: most recent school year with reported KPI/performance data.
    private const CURRENT_YEAR = '2024-2025';
    // Most recent grading period for that year — used as the dashboard's single "current" snapshot.
    private const CURRENT_TERM = 3;

    public function index()
    {
        $documentModel = new DocumentModel();

        $kpi = (new KpiSnapshotModel())->latest();
        if ($kpi) {
            $kpi['submission_compliance'] = $documentModel->submissionComplianceRate() ?? $kpi['submission_compliance'];
        }

        $enrollment = (new EnrollmentByLevelModel())->where('school_year', self::CURRENT_YEAR)->orderBy('grade_level')->findAll();
        $perfLevel  = (new PerformanceByLevelModel())->where('school_year', self::CURRENT_YEAR)->where('term', self::CURRENT_TERM)->orderBy('grade_level')->findAll();

        $perfSubjectModel = new PerformanceBySubjectModel();
        $perfSubjectAsc   = $perfSubjectModel->where('school_year', self::CURRENT_YEAR)->where('term', self::CURRENT_TERM)->orderBy('mps', 'ASC')->findAll();
        $lowest           = $perfSubjectAsc[0] ?? null;
        $allPerf          = $perfSubjectModel->where('school_year', self::CURRENT_YEAR)->where('term', self::CURRENT_TERM)->orderBy('mps', 'DESC')->findAll();

        $docSummary    = $documentModel->statusCounts();
        $recentDocs    = $documentModel->allWithTeacher(null, 5);

        return view('pages/admin/dashboard', [
            'pageTitle'   => 'Admin Dashboard',
            'kpi'         => $kpi,
            'enrollment'  => $enrollment,
            'perfLevel'   => $perfLevel,
            'lowest'      => $lowest,
            'allPerf'     => $allPerf,
            'docSummary'  => $docSummary,
            'recentDocs'  => $recentDocs,
        ]);
    }
}
