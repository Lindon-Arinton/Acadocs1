<?php

namespace App\Controllers\Api;

use App\Models\EnrollmentByLevelModel;
use App\Models\KpiSnapshotModel;
use App\Models\PerformanceByLevelModel;
use App\Models\PerformanceBySubjectModel;

class PerformanceController extends BaseApiController
{
    public function index()
    {
        $schoolYear = $this->request->getGet('school_year') ?? '2025-2026';
        $term       = (int) ($this->request->getGet('term') ?? 1);
        if (! in_array($term, [1, 2, 3], true)) {
            $term = 1;
        }

        return $this->jsonResponse([
            'kpi'        => (new KpiSnapshotModel())->forYear($schoolYear),
            'by_level'   => (new PerformanceByLevelModel())->where('school_year', $schoolYear)->where('term', $term)->orderBy('grade_level')->findAll(),
            'by_subject' => (new PerformanceBySubjectModel())->where('school_year', $schoolYear)->where('term', $term)->orderBy('mps', 'DESC')->findAll(),
            'enrollment' => (new EnrollmentByLevelModel())->where('school_year', $schoolYear)->orderBy('grade_level')->findAll(),
        ]);
    }
}
