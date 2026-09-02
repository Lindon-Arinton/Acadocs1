<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Libraries\MpsCalculator;
use App\Libraries\MpsScoreImporter;
use App\Models\MpsTestScoreModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PerformanceMps extends BaseController
{
    public const YEAR_OPTIONS = ['2026-2027', '2025-2026', '2024-2025', '2023-2024', '2022-2023'];
    public const TERM_OPTIONS = [1, 2, 3];

    public const GRADE_LEVELS = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
    public const SUBJECTS = [
        'English', 'Filipino', 'Science', 'Mathematics', 'AP', 'TLE',
        'MAPEH', 'Music', 'Arts', 'PE', 'Health', 'ESP',
    ];

    /** Short form-field keys mapped to the DB's full test_period labels. */
    public const PERIOD_MAP = [
        's1'   => 'Summative Test 1',
        's2'   => 'Summative Test 2',
        'exam' => 'Term Examination',
    ];

    /** Matches "2025-2026" — the only shape a school year needs to satisfy now that it's freely typed rather than picked from a fixed list. */
    private const YEAR_PATTERN = '/^\d{4}-\d{4}$/';

    public function index()
    {
        if (! hasRole('teacher')) {
            return redirect()->to('/dashboard');
        }

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            $year = trim($this->request->getPost('school_year') ?? '');
            $term = (int) $this->request->getPost('term');

            if (! preg_match(self::YEAR_PATTERN, $year) || ! in_array($term, self::TERM_OPTIONS, true)) {
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

        $year = trim($this->request->getGet('year') ?? '') ?: self::YEAR_OPTIONS[0];
        if (! preg_match(self::YEAR_PATTERN, $year)) {
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

        return view('pages/teacher/performance_mps', [
            'pageTitle'   => 'Enter MPS Scores',
            'year'        => $year,
            'term'        => $term,
            'years'       => $this->availableYears(),
            'terms'       => self::TERM_OPTIONS,
            'gradeLevels' => self::GRADE_LEVELS,
            'subjects'    => self::SUBJECTS,
            'periods'     => self::PERIOD_MAP,
            'existing'    => $existing,
            'flash'       => session()->getFlashdata('flash'),
        ]);
    }

    /**
     * School years to suggest in the datalist — the hardcoded baseline plus
     * any year that already has scores entered, newest first. Purely a
     * convenience list now; typing any other "YYYY-YYYY" year is still valid.
     *
     * @return array<int,string>
     */
    private function availableYears(): array
    {
        $fromScores = array_column(
            (new MpsTestScoreModel())->select('school_year')->distinct()->findAll(),
            'school_year'
        );

        $years = array_unique(array_merge(self::YEAR_OPTIONS, $fromScores));
        rsort($years);

        return array_values($years);
    }

    public function import()
    {
        if (! hasRole('teacher')) {
            return redirect()->to('/dashboard');
        }

        $isAjax   = $this->request->isAJAX();
        $redirect = '/performance/mps';

        $year = trim($this->request->getPost('school_year') ?? '');
        $term = (int) $this->request->getPost('term');

        if (! preg_match(self::YEAR_PATTERN, $year) || ! in_array($term, self::TERM_OPTIONS, true)) {
            return $isAjax ? $this->ajaxError('Invalid school year or term.') : redirect()->to($redirect);
        }

        $redirect = '/performance/mps?year=' . urlencode($year) . '&term=' . $term;

        $file = $this->request->getFile('import_file');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $isAjax ? $this->ajaxError('Please choose a valid Excel file to upload.') : redirect()->to($redirect);
        }

        if (! in_array(strtolower($file->getExtension() ?: ''), ['xlsx', 'xls'], true)) {
            return $isAjax ? $this->ajaxError('Only .xlsx or .xls files are supported.') : redirect()->to($redirect);
        }

        $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'mps_imports';
        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $fileName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientName());

        if (! $file->move($uploadPath, $fileName)) {
            return $isAjax ? $this->ajaxError('Could not save the uploaded file.') : redirect()->to($redirect);
        }

        try {
            $summary = (new MpsScoreImporter())->import($uploadPath . DIRECTORY_SEPARATOR . $fileName, $year, $term);
        } catch (\Throwable $e) {
            return $isAjax ? $this->ajaxError('Import failed: ' . $e->getMessage()) : redirect()->to($redirect);
        }

        if ($summary['saved'] === 0) {
            $message = 'No scores were imported. ' . implode(' ', array_merge($summary['errors'], $summary['warnings']));

            if ($isAjax) {
                return $this->ajaxError(trim($message));
            }

            session()->setFlashdata('flash', ['type' => 'danger', 'msg' => trim($message)]);

            return redirect()->to($redirect);
        }

        $message = sprintf(
            'Imported %d score(s) from %s for Term %d, SY %s.',
            $summary['saved'],
            implode(', ', $summary['periods_found']),
            $term,
            $year,
        );

        if ($summary['warnings'] !== []) {
            $message .= ' ' . implode(' ', $summary['warnings']);
        }

        if ($isAjax) {
            return $this->ajaxSuccess($message, ['summary' => $summary, 'redirect' => $redirect]);
        }

        session()->setFlashdata('flash', ['type' => $summary['warnings'] === [] ? 'success' : 'warning', 'msg' => $message]);

        return redirect()->to($redirect);
    }

    public function template()
    {
        if (! hasRole('teacher')) {
            return redirect()->to('/dashboard');
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('MPS');

        $row = 1;
        foreach (self::PERIOD_MAP as $label) {
            $sheet->setCellValue("A{$row}", strtoupper($label));
            $row += 2;

            $sheet->fromArray(array_merge([null], self::SUBJECTS), null, "A{$row}");
            $row++;

            foreach (self::GRADE_LEVELS as $grade) {
                $sheet->setCellValue("A{$row}", strtoupper($grade));
                $row++;
            }

            $row += 2; // blank separator row before the next section
        }

        foreach (range('A', chr(ord('A') + count(self::SUBJECTS))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mps_template_' . uniqid() . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempFile);

        return $this->response->download($tempFile, null)->setFileName('mps_scores_template.xlsx');
    }
}
