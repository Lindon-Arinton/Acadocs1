<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\KpiSnapshotModel;
use App\Models\PerformanceByLevelModel;
use App\Models\PerformanceBySubjectModel;

class Performance extends BaseController
{
    // Hardcoded: current school year plus the 3 preceding ones.
    private const YEAR_OPTIONS = ['2025-2026', '2024-2025', '2023-2024', '2022-2023'];
    private const TERM_OPTIONS = [1, 2, 3];

    public function index()
    {
        $year = $this->request->getGet('year') ?? self::YEAR_OPTIONS[0];
        if (!in_array($year, self::YEAR_OPTIONS, true)) {
            $year = self::YEAR_OPTIONS[0];
        }

        $term = (int) ($this->request->getGet('term') ?? self::TERM_OPTIONS[0]);
        if (! in_array($term, self::TERM_OPTIONS, true)) {
            $term = self::TERM_OPTIONS[0];
        }

        $data = [
            'year'      => $year,
            'term'      => $term,
            'byLevel'   => (new PerformanceByLevelModel())->where('school_year', $year)->where('term', $term)->orderBy('grade_level')->findAll(),
            'bySubject' => (new PerformanceBySubjectModel())->where('school_year', $year)->where('term', $term)->orderBy('mps', 'DESC')->findAll(),
            'kpi'       => (new KpiSnapshotModel())->forYear($year) ?? [],
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'html'      => view('pages/admin/performance_content', $data, ['debug' => false]),
                'byLevel'   => $data['byLevel'],
                'bySubject' => $data['bySubject'],
            ]);
        }

        return view('pages/admin/performance', array_merge($data, [
            'pageTitle'   => 'Performance Analytics',
            'years'       => self::YEAR_OPTIONS,
            'termOptions' => self::TERM_OPTIONS,
        ]));
    }
}
