<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\KpiSnapshotModel;
use App\Models\PerformanceByLevelModel;
use App\Models\PerformanceBySubjectModel;

class Performance extends BaseController
{
    // Hardcoded: most recent school year with reported data, plus the 3 preceding ones.
    private const YEAR_OPTIONS = ['2024-2025', '2023-2024', '2022-2023', '2021-2022'];
    private const TERM_OPTIONS = [1, 2, 3];

    public function index()
    {
        $years = $this->availableYears();

        $year = $this->request->getGet('year') ?? $years[0];
        if (!in_array($year, $years, true)) {
            $year = $years[0];
        }

        $term = (int) ($this->request->getGet('term') ?? self::TERM_OPTIONS[0]);
        if (! in_array($term, self::TERM_OPTIONS, true)) {
            $term = self::TERM_OPTIONS[0];
        }

        $kpi = (new KpiSnapshotModel())->forYear($year) ?? [];
        if ($kpi) {
            $kpi['submission_compliance'] = (new DocumentModel())->submissionComplianceRate() ?? $kpi['submission_compliance'];
        }

        $data = [
            'year'      => $year,
            'term'      => $term,
            'byLevel'   => (new PerformanceByLevelModel())->where('school_year', $year)->where('term', $term)->orderBy('grade_level')->findAll(),
            'bySubject' => (new PerformanceBySubjectModel())->where('school_year', $year)->where('term', $term)->orderBy('mps', 'DESC')->findAll(),
            'kpi'       => $kpi,
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
            'years'       => $years,
            'termOptions' => self::TERM_OPTIONS,
        ]));
    }

    /**
     * School years available to filter on: the hardcoded starting list plus
     * whatever years already have MPS data reported, newest first — so a
     * newly entered school year (like a fresh school year's Term 1 scores)
     * shows up without a code change.
     */
    private function availableYears(): array
    {
        $fromLevel = array_column(
            (new PerformanceByLevelModel())->select('school_year')->distinct()->findAll(),
            'school_year'
        );
        $fromSubject = array_column(
            (new PerformanceBySubjectModel())->select('school_year')->distinct()->findAll(),
            'school_year'
        );

        $years = array_unique(array_merge(self::YEAR_OPTIONS, $fromLevel, $fromSubject));
        rsort($years);

        return array_values($years);
    }
}
