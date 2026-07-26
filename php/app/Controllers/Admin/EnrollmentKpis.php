<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DepedKpiReportModel;
use App\Models\EnrollmentByLevelModel;

class EnrollmentKpis extends BaseController
{
    // Hardcoded: the school years covered by the client's provided DepEd KPI reports.
    private const YEAR_OPTIONS = ['2023-2024', '2022-2023', '2021-2022', '2020-2021'];

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

        return view('pages/admin/enrollment_kpis', array_merge($data, [
            'pageTitle' => 'Enrollment KPIs',
            'years'     => self::YEAR_OPTIONS,
            'depedKpis' => (new DepedKpiReportModel())->allByYear(),
        ]));
    }
}
