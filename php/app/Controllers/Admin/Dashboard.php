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

        $years = $this->availableYears();

        $requestedYear = $this->request->getGet('year');
        $currentYear   = in_array($requestedYear, $years, true) ? $requestedYear : ($years[0] ?? self::CURRENT_YEAR);

        // kpi_snapshots (and the constant above) can go stale — nothing in the app
        // ever writes to that table, so use whichever term actually has the most
        // recent MPS data reported for the selected year.
        $latestPeriod = (new PerformanceByLevelModel())
            ->select('term')
            ->where('school_year', $currentYear)
            ->orderBy('term', 'DESC')
            ->first();

        $currentTerm = $latestPeriod['term'] ?? self::CURRENT_TERM;

        $enrollment = (new EnrollmentByLevelModel())->where('school_year', $currentYear)->orderBy('grade_level')->findAll();
        $perfLevel  = (new PerformanceByLevelModel())->where('school_year', $currentYear)->where('term', $currentTerm)->orderBy('grade_level')->findAll();

        $avgMps = $perfLevel !== [] ? round(array_sum(array_column($perfLevel, 'mps')) / count($perfLevel), 2) : null;

        $perfSubjectModel = new PerformanceBySubjectModel();
        $perfSubjectAsc   = $perfSubjectModel->where('school_year', $currentYear)->where('term', $currentTerm)->orderBy('mps', 'ASC')->findAll();
        $lowest           = $perfSubjectAsc[0] ?? null;
        $allPerf          = $perfSubjectModel->where('school_year', $currentYear)->where('term', $currentTerm)->orderBy('mps', 'DESC')->findAll();

        // General average per subject across all grade levels — the default, uncluttered
        // view; $allPerf (per grade+instructor) is only shown when the user clicks "View All".
        $scoresBySubject = [];
        foreach ($allPerf as $p) {
            $scoresBySubject[$p['subject']][] = (float) $p['mps'];
        }
        $avgPerf = [];
        foreach ($scoresBySubject as $subject => $scores) {
            $avgPerf[] = [
                'subject' => $subject,
                'mps'     => round(array_sum($scores) / count($scores), 2),
            ];
        }
        usort($avgPerf, static fn ($a, $b) => $b['mps'] <=> $a['mps']);

        $docSummary    = $documentModel->statusCounts();
        $recentDocs    = $documentModel->allWithTeacher(null, 5);

        return view('pages/admin/dashboard', [
            'pageTitle'   => 'Admin Dashboard',
            'kpi'         => $kpi,
            'avgMps'      => $avgMps,
            'years'       => $years,
            'currentYear' => $currentYear,
            'currentTerm' => $currentTerm,
            'enrollment'  => $enrollment,
            'perfLevel'   => $perfLevel,
            'lowest'      => $lowest,
            'allPerf'     => $allPerf,
            'avgPerf'     => $avgPerf,
            'docSummary'  => $docSummary,
            'recentDocs'  => $recentDocs,
        ]);
    }

    /**
     * School years with reported performance or enrollment data, newest
     * first. Replaces the old hardcoded CURRENT_YEAR so the dashboard keeps
     * working as new years get data without a code change.
     */
    private function availableYears(): array
    {
        $fromPerf = array_column(
            (new PerformanceByLevelModel())->select('school_year')->distinct()->findAll(),
            'school_year'
        );
        $fromEnrollment = array_column(
            (new EnrollmentByLevelModel())->select('school_year')->distinct()->findAll(),
            'school_year'
        );

        $years = array_unique(array_merge($fromPerf, $fromEnrollment, [self::CURRENT_YEAR]));
        rsort($years);

        return array_values($years);
    }
}
