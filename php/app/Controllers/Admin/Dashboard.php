<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DepedKpiReportModel;
use App\Models\DocumentModel;
use App\Models\EnrollmentByLevelModel;
use App\Models\PerformanceByLevelModel;
use App\Models\PerformanceBySubjectModel;

class Dashboard extends BaseController
{
    // Hardcoded: most recent school year with reported KPI/performance data.
    private const CURRENT_YEAR = '2024-2025';
    // Most recent grading period for that year — used as the dashboard's single "current" snapshot.
    private const CURRENT_TERM = 3;

    // Hardcoded: the school years covered by the client's provided DepEd KPI reports.
    private const YEAR_OPTIONS = ['2023-2024', '2022-2023', '2021-2022', '2020-2021'];

    // Fallback only: MPS isn't reported in the DepEd KPI docs above — it lives
    // in the same performance_by_level table Performance Analytics reads
    // from. The actual source year is always resolved dynamically (see
    // index() below) to whichever year has the most recent scores, so this
    // never goes stale the way a hardcoded year would.
    private const MPS_SOURCE_YEAR = '2024-2025';

    public function index()
    {
        $documentModel = new DocumentModel();

        $years = $this->availableYears();

        $requestedYear = $this->request->getGet('year');
        $currentYear   = in_array($requestedYear, $years, true) ? $requestedYear : ($years[0] ?? self::CURRENT_YEAR);

        // Nothing in the app writes to kpi_snapshots, so the school-wide KPI
        // cards are sourced the same way the old Enrollment & KPIs page did:
        // live-computed from enrollment/performance/DepEd-report data below.
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

        $docSummary     = $documentModel->statusCounts();
        $recentDocs     = $documentModel->allWithTeacher(null, 5);
        $complianceRate = $documentModel->submissionComplianceRate();

        // Enrollment KPIs section (merged from the old Admin\EnrollmentKpis::index()).
        $latestMpsYear = (new PerformanceByLevelModel())->select('school_year')->orderBy('school_year', 'DESC')->first();
        $mpsSourceYear = $latestMpsYear['school_year'] ?? self::MPS_SOURCE_YEAR;

        $mpsByTerm = (new PerformanceByLevelModel())
            ->where('school_year', $mpsSourceYear)
            ->orderBy('term')->orderBy('grade_level')
            ->findAll();

        $mpsTrend = [];
        foreach ($mpsByTerm as $row) {
            $term = (int) $row['term'];
            $mpsTrend[$term]['term']     = $term;
            $mpsTrend[$term]['scores'][] = (float) $row['mps'];
        }
        foreach ($mpsTrend as &$t) {
            $t['avg_mps'] = round(array_sum($t['scores']) / count($t['scores']), 2);
            unset($t['scores']);
        }
        unset($t);
        $mpsTrend = array_values($mpsTrend);

        $mpsOverallAvg = $mpsTrend
            ? round(array_sum(array_column($mpsTrend, 'avg_mps')) / count($mpsTrend), 2)
            : null;

        // allByYear() is already ASC by school_year — the same order both the
        // historical table and these trend sparklines want (oldest -> newest).
        $depedKpis        = (new DepedKpiReportModel())->allByYear();
        $enrollmentTotals = $this->enrollmentTotalsByYear();
        usort($enrollmentTotals, static fn ($a, $b) => $a['school_year'] <=> $b['school_year']);

        // Year-over-year MPS (distinct from $mpsTrend above, which is term-by-term
        // *within* $mpsSourceYear for the drill-down chart) — this is what the
        // "Average MPS" tile's sparkline/delta compare against: last year's
        // school-wide average, not last term's.
        $avgMpsByYear = $this->avgMpsByYear();

        $enrolleesSeries = array_map(
            static fn ($r) => ['label' => 'SY ' . $r['school_year'], 'value' => $r['total']],
            $enrollmentTotals
        );
        $dropoutSeries = array_map(
            static fn ($r) => ['label' => 'SY ' . $r['school_year'], 'value' => $r['dropout_rate']],
            $depedKpis
        );
        $mpsYearSeries = array_map(
            static fn ($r) => ['label' => 'SY ' . $r['school_year'], 'value' => $r['avg_mps']],
            $avgMpsByYear
        );

        return view('pages/admin/dashboard', [
            'pageTitle'          => 'Admin Dashboard',
            'avgMps'             => $avgMps,
            'years'              => $years,
            'year'               => $currentYear,
            'currentYear'        => $currentYear,
            'currentTerm'        => $currentTerm,
            'enrollment'         => $enrollment,
            'perfLevel'          => $perfLevel,
            'lowest'             => $lowest,
            'allPerf'            => $allPerf,
            'avgPerf'            => $avgPerf,
            'docSummary'         => $docSummary,
            'recentDocs'         => $recentDocs,
            'complianceRate'     => $complianceRate,
            'depedKpis'          => $depedKpis,
            'enrollmentTotals'   => $enrollmentTotals,
            'mpsTrend'           => $mpsTrend,
            'mpsSourceYear'      => $mpsSourceYear,
            'mpsOverallAvg'      => $mpsOverallAvg,
            'enrolleesSparkline' => $this->sparkline(array_column($enrolleesSeries, 'value')),
            'dropoutSparkline'   => $this->sparkline(array_column($dropoutSeries, 'value')),
            'mpsSparkline'       => $this->sparkline(array_column($mpsYearSeries, 'value')),
            // Enrollees reads more naturally as a % change (headcount), dropout/MPS
            // as a point change (they're already percentages). "Good" direction is
            // metric-specific: up is good for enrollees/MPS, down is good for dropout.
            'enrolleesDelta'     => $this->seriesDelta($enrolleesSeries, true),
            'dropoutDelta'       => $this->seriesDelta($dropoutSeries, false),
            'mpsDelta'           => $this->seriesDelta($mpsYearSeries, false),
        ]);
    }

    /**
     * Renders a small trend series as SVG polyline points (64x22 viewBox) for
     * the KPI stat-tile sparklines, plus the same points closed into a filled
     * area shape. Returns null when there's nothing to draw a trend from
     * (fewer than 2 real data points).
     *
     * @param array<int,mixed> $values
     * @return array{points:string,areaPoints:string,lastX:float,lastY:float}|null
     */
    private function sparkline(array $values): ?array
    {
        $values = array_values(array_filter($values, static fn ($v) => $v !== null));
        if (count($values) < 2) {
            return null;
        }

        $width  = 64;
        $height = 22;
        $pad    = 2;
        $min    = min($values);
        $max    = max($values);
        $range  = $max - $min ?: 1;

        $points = [];
        $count  = count($values);
        foreach ($values as $i => $v) {
            $x = $count > 1 ? round(($i / ($count - 1)) * $width, 1) : round($width / 2, 1);
            $y = round($height - $pad - ((($v - $min) / $range) * ($height - 2 * $pad)), 1);
            $points[] = $x . ',' . $y;
            if ($i === $count - 1) {
                $lastX = $x;
                $lastY = $y;
            }
        }

        $pointsStr = implode(' ', $points);
        $areaPoints = '0,' . $height . ' ' . $pointsStr . ' ' . $width . ',' . $height;

        return ['points' => $pointsStr, 'areaPoints' => $areaPoints, 'lastX' => $lastX, 'lastY' => $lastY];
    }

    /**
     * Change between the last two entries of a chronological {label, value}
     * series, plus the label of the "previous" entry compared against (for a
     * "vs SY ..." caption). Null when there's no earlier entry to compare to.
     * $asPercent picks % change (headcounts) vs raw point change (a series
     * that's already a rate/percentage, where the meaningful delta is
     * percentage *points*, not a percent-of-a-percent).
     *
     * @param array<int,array{label:string,value:mixed}> $series
     * @return array{delta:float,vsLabel:string}|null
     */
    private function seriesDelta(array $series, bool $asPercent): ?array
    {
        $series = array_values(array_filter($series, static fn ($r) => $r['value'] !== null));
        if (count($series) < 2) {
            return null;
        }

        $current  = (float) $series[count($series) - 1]['value'];
        $previous = $series[count($series) - 2];
        $prevVal  = (float) $previous['value'];

        if ($asPercent) {
            if ($prevVal == 0.0) {
                return null;
            }

            $delta = round(($current - $prevVal) / abs($prevVal) * 100, 2);
        } else {
            $delta = round($current - $prevVal, 2);
        }

        return ['delta' => $delta, 'vsLabel' => $previous['label']];
    }

    /**
     * School-wide average MPS per school year (across every term and grade
     * level reported that year) — the year-over-year series the "Average
     * MPS" stat tile's sparkline/delta compare against.
     *
     * @return array<int,array{school_year:string,avg_mps:float}>
     */
    private function avgMpsByYear(): array
    {
        $rows = (new PerformanceByLevelModel())
            ->select('school_year, AVG(mps) AS avg_mps')
            ->groupBy('school_year')
            ->orderBy('school_year', 'ASC')
            ->findAll();

        return array_map(
            static fn ($r) => ['school_year' => $r['school_year'], 'avg_mps' => round((float) $r['avg_mps'], 2)],
            $rows
        );
    }

    /**
     * School years with reported performance, enrollment, or DepEd KPI data,
     * newest first. Replaces the old hardcoded CURRENT_YEAR so the dashboard
     * keeps working as new years get data without a code change.
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
        $fromKpi = array_column((new DepedKpiReportModel())->allByYear(), 'school_year');

        $years = array_unique(array_merge(self::YEAR_OPTIONS, $fromPerf, $fromEnrollment, $fromKpi, [self::CURRENT_YEAR]));
        rsort($years);

        return array_values($years);
    }

    /**
     * Total enrollees per school year. DepEd KPI reports capture this two
     * different ways depending on the document's age: newer ones have a
     * per-grade-level breakdown table (summed here), older ones have a single
     * combined "Enrolment: X = male Y female Z" row (captured as
     * deped_kpi_reports.enrolment_total instead). A given year only ever has
     * one or the other, never both, so the breakdown table wins when present
     * and the combined-row figure is used as a fallback.
     *
     * @return array<int,array{school_year:string,total:int}>
     */
    private function enrollmentTotalsByYear(): array
    {
        $totals = [];

        $gradeLevelRows = (new EnrollmentByLevelModel())
            ->select('school_year, SUM(students) AS total')
            ->groupBy('school_year')
            ->findAll();

        foreach ($gradeLevelRows as $row) {
            $totals[$row['school_year']] = (int) $row['total'];
        }

        foreach ((new DepedKpiReportModel())->allByYear() as $row) {
            if (! isset($totals[$row['school_year']]) && $row['enrolment_total'] !== null) {
                $totals[$row['school_year']] = (int) $row['enrolment_total'];
            }
        }

        $result = [];
        foreach ($totals as $schoolYear => $total) {
            $result[] = ['school_year' => $schoolYear, 'total' => $total];
        }

        return $result;
    }
}
