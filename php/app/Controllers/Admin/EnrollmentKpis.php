<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EnrollmentByLevelModel;
use App\Models\KpiSnapshotModel;

class EnrollmentKpis extends BaseController
{
    // Hardcoded: current school year plus the 3 preceding ones.
    private const YEAR_OPTIONS = ['2025-2026', '2024-2025', '2023-2024', '2022-2023'];

    public function index()
    {
        $year = $this->request->getGet('year') ?? self::YEAR_OPTIONS[0];
        if (!in_array($year, self::YEAR_OPTIONS, true)) {
            $year = self::YEAR_OPTIONS[0];
        }

        $enrollment = (new EnrollmentByLevelModel())->where('school_year', $year)->orderBy('grade_level')->findAll();

        $data = [
            'year'       => $year,
            'kpi'        => (new KpiSnapshotModel())->forYear($year) ?? [],
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
        ]));
    }
}
