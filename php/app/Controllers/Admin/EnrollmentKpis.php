<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DepedKpiReportModel;
use App\Models\EnrollmentByLevelModel;
use App\Models\PerformanceByLevelModel;

class EnrollmentKpis extends BaseController
{
    // Hardcoded: the school years covered by the client's provided DepEd KPI reports.
    private const YEAR_OPTIONS = ['2023-2024', '2022-2023', '2021-2022', '2020-2021'];

    // MPS isn't reported in the DepEd KPI docs above — it lives in the same
    // performance_by_level table Performance Analytics reads from, and the
    // only school year with real scores there is 2024-2025.
    private const MPS_SOURCE_YEAR = '2024-2025';

    public function index()
    {
        $year = $this->request->getGet('year') ?? self::YEAR_OPTIONS[0];
        if (!in_array($year, self::YEAR_OPTIONS, true)) {
            $year = self::YEAR_OPTIONS[0];
        }

        $enrollment = (new EnrollmentByLevelModel())->where('school_year', $year)->orderBy('grade_level')->findAll();

        $data = [
            'year'       => $year,
            'enrollment' => $enrollment,
            'total'      => array_sum(array_column($enrollment, 'students')),
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'html'       => view('pages/admin/enrollment_kpis_content', $data, ['debug' => false]),
                'enrollment' => $data['enrollment'],
            ]);
        }

        $mpsByTerm = (new PerformanceByLevelModel())
            ->where('school_year', self::MPS_SOURCE_YEAR)
            ->orderBy('term')->orderBy('grade_level')
            ->findAll();

        $mpsTrend = [];
        foreach ($mpsByTerm as $row) {
            $term = (int) $row['term'];
            $mpsTrend[$term]['term']   = $term;
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

        return view('pages/admin/enrollment_kpis', array_merge($data, [
            'pageTitle'      => 'Enrollment KPIs',
            'years'          => self::YEAR_OPTIONS,
            'depedKpis'      => (new DepedKpiReportModel())->allByYear(),
            'mpsTrend'       => $mpsTrend,
            'mpsSourceYear'  => self::MPS_SOURCE_YEAR,
            'mpsOverallAvg'  => $mpsOverallAvg,
        ]));
    }
}
