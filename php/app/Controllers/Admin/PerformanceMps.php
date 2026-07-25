<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\MpsCalculator;
use App\Models\MpsTestScoreModel;

class PerformanceMps extends BaseController
{
    private const YEAR_OPTIONS = ['2025-2026', '2024-2025', '2023-2024', '2022-2023'];
    private const TERM_OPTIONS = [1, 2, 3];

    public const GRADE_LEVELS = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
    public const SUBJECTS = [
        'English', 'Filipino', 'Science', 'Mathematics', 'AP', 'TLE',
        'MAPEH', 'Music', 'Arts', 'PE', 'Health', 'ESP',
    ];

    /** Short form-field keys mapped to the DB's full test_period labels. */
    private const PERIOD_MAP = [
        's1'   => 'Summative Test 1',
        's2'   => 'Summative Test 2',
        'exam' => 'Term Examination',
    ];

    public function index()
    {
        if (! hasRole('admin')) {
            return redirect()->to('/dashboard');
        }

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            $year = $this->request->getPost('school_year');
            $term = (int) $this->request->getPost('term');

            if (! in_array($year, self::YEAR_OPTIONS, true) || ! in_array($term, self::TERM_OPTIONS, true)) {
                return $isAjax ? $this->ajaxError('Invalid school year or term.') : redirect()->to('/performance/mps');
            }

            $redirect = '/performance/mps?year=' . urlencode($year) . '&term=' . $term;

            $rawScores = $this->request->getPost('scores') ?? [];
            $scoresByPeriod = [];
            foreach (self::PERIOD_MAP as $shortKey => $label) {
                $scoresByPeriod[$label] = $rawScores[$shortKey] ?? [];
            }

            try {
                (new MpsCalculator())->saveScores($year, $term, $scoresByPeriod);
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to($redirect);
            }

            $message = 'MPS scores saved for Term ' . $term . ', SY ' . $year . '.';

            if ($isAjax) {
                return $this->ajaxSuccess($message);
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message]);

            return redirect()->to($redirect);
        }

        $year = $this->request->getGet('year') ?? self::YEAR_OPTIONS[0];
        if (! in_array($year, self::YEAR_OPTIONS, true)) {
            $year = self::YEAR_OPTIONS[0];
        }

        $term = (int) ($this->request->getGet('term') ?? self::TERM_OPTIONS[0]);
        if (! in_array($term, self::TERM_OPTIONS, true)) {
            $term = self::TERM_OPTIONS[0];
        }

        $existingRows = (new MpsTestScoreModel())->forYearTerm($year, $term);

        $existing = [];
        $periodByLabel = array_flip(self::PERIOD_MAP);
        foreach ($existingRows as $row) {
            $shortKey = $periodByLabel[$row['test_period']] ?? null;
            if ($shortKey === null) {
                continue;
            }
            $existing[$shortKey][$row['grade_level']][$row['subject']] = $row['mps'];
        }

        return view('pages/admin/performance_mps', [
            'pageTitle'   => 'Enter MPS Scores',
            'year'        => $year,
            'term'        => $term,
            'years'       => self::YEAR_OPTIONS,
            'terms'       => self::TERM_OPTIONS,
            'gradeLevels' => self::GRADE_LEVELS,
            'subjects'    => self::SUBJECTS,
            'periods'     => self::PERIOD_MAP,
            'existing'    => $existing,
            'flash'       => session()->getFlashdata('flash'),
        ]);
    }
}
