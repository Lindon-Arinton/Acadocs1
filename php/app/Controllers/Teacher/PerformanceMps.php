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

<<<<<<< Updated upstream
            $rawScores = $this->request->getPost('scores') ?? [];
            $scoresByPeriod = [];
=======
            $rawScores    = $this->request->getPost('scores') ?? [];
            $handledCells = $this->handledCells($this->currentTeacherRow($year, $term));

            // Belt-and-suspenders: the form only ever renders inputs for the
            // signed-in teacher's own grade/subject/section cells, but a crafted
            // POST could still try to smuggle in someone else's — resolve every
            // posted value against handledCells and drop anything that isn't
            // actually theirs before it ever reaches the DB.
            $entries = [];
>>>>>>> Stashed changes
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

<<<<<<< Updated upstream
=======
        $handledCells = $this->handledCells($this->currentTeacherRow($year, $term));

>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
            $summary = (new MpsScoreImporter())->import($uploadPath . DIRECTORY_SEPARATOR . $fileName, $year, $term);
=======
            $summary = (new MpsScoreImporter())->import(
                $uploadPath . DIRECTORY_SEPARATOR . $fileName,
                $year,
                $term,
                $this->gradeSubjectOnly($this->handledCells($this->currentTeacherRow($year, $term)))
            );
>>>>>>> Stashed changes
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

<<<<<<< Updated upstream
=======
        $year = trim($this->request->getGet('year') ?? '');
        $term = (int) $this->request->getGet('term');
        if (! preg_match(self::YEAR_PATTERN, $year) || ! in_array($term, self::TERM_OPTIONS, true)) {
            [$year, $term] = [null, null];
        }

        $handledCells = $this->handledCells($this->currentTeacherRow($year, $term));
        $subjects     = $this->subjectsFromCells($handledCells);
        $gradeLevels  = $this->gradesFromCells($handledCells);

        if ($subjects === [] || $gradeLevels === []) {
            session()->setFlashdata('flash', [
                'type' => 'danger',
                'msg'  => 'You have no subjects for this term yet — add them under My Profile → Subject Load.',
            ]);

            return redirect()->to('/performance/mps');
        }

>>>>>>> Stashed changes
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
<<<<<<< Updated upstream
=======

    /**
     * The current session's teacher row, joined with their subject load for
     * the given school year + term (see TeacherModel::findWithSubjects()).
     */
    private function currentTeacherRow(?string $year = null, ?int $term = null): ?array
    {
        $teacherModel = new TeacherModel();
        $teacher      = $teacherModel->resolveForUser(currentUser());

        if (! $teacher) {
            return null;
        }

        return $teacherModel->findWithSubjects((int) $teacher['id'], $year, $term) ?? $teacher;
    }

    /**
     * Resolves a teacher's row into the exact set of Grade+Subject+Section
     * cells they're allowed to enter MPS for, from their structured
     * teacher_subjects rows (subject code, grade_level, section — one row
     * per section actually handled, see TeacherSeeder). A row created before
     * sections existed, or via a path that doesn't set grade_level/section
     * (e.g. the mobile app's flat subject-string API), falls back to parsing
     * its free-text subject (e.g. "MAPEH 9 (5)") the same way this used to
     * work — those collapse into a single section-less cell per grade+subject,
     * since no real section breakdown is on record for them.
     *
     * @return array<string,array{grade:string,subject:string,section:?string}>
     *   keyed by "Grade X|Subject|SectionLabel" — SectionLabel is the real
     *   section name, or NO_SECTION for a section-less (legacy) cell.
     */
    private function handledCells(?array $teacher): array
    {
        if (! $teacher) {
            return [];
        }

        $fallbackGrades = $this->parseGradeTokens($teacher['grade_level'] ?? null);
        $cells          = [];

        foreach ($teacher['subjects'] ?? [] as $row) {
            $rawSubject = trim((string) ($row['subject'] ?? ''));
            if ($rawSubject === '') {
                continue;
            }

            $gradeLevel = $row['grade_level'] ?? null;

            if ($gradeLevel !== null) {
                $subject = $this->resolveMpsSubject($rawSubject);
                if ($subject === null) {
                    continue;
                }

                $section = $row['section'] ?? null;
                $label   = $section ?? self::NO_SECTION;
                $cells[$gradeLevel . '|' . $subject . '|' . $label] = [
                    'grade'   => $gradeLevel,
                    'subject' => $subject,
                    'section' => $section,
                ];

                continue;
            }

            // Legacy free-text row — no grade_level/section columns set.
            $parsed = $this->parseSubjectEntry($rawSubject);
            if ($parsed === null) {
                continue;
            }

            $grades = $parsed['grade'] !== null ? [$parsed['grade']] : $fallbackGrades;
            foreach ($grades as $grade) {
                $cells[$grade . '|' . $parsed['subject'] . '|' . self::NO_SECTION] = [
                    'grade'   => $grade,
                    'subject' => $parsed['subject'],
                    'section' => null,
                ];
            }
        }

        return $cells;
    }

    private function resolveMpsSubject(string $rawCode): ?string
    {
        $code = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $rawCode));

        return self::SUBJECT_ALIASES[$code] ?? null;
    }

    /** @param array<string,array{grade:string,subject:string,section:?string}> $cells */
    private function gradesFromCells(array $cells): array
    {
        $found = array_unique(array_map(static fn (array $cell) => $cell['grade'], $cells));

        return array_values(array_intersect(self::GRADE_LEVELS, $found));
    }

    /** @param array<string,array{grade:string,subject:string,section:?string}> $cells */
    private function subjectsFromCells(array $cells): array
    {
        $found = array_unique(array_map(static fn (array $cell) => $cell['subject'], $cells));

        return array_values(array_intersect(self::SUBJECTS, $found));
    }

    /** Collapses handledCells down to "Grade X|Subject" => true — for the Excel importer/template, which don't break scores out by section. */
    private function gradeSubjectOnly(array $cells): array
    {
        $out = [];
        foreach ($cells as $cell) {
            $out[$cell['grade'] . '|' . $cell['subject']] = true;
        }

        return $out;
    }

    /**
     * Nests handledCells into Grade -> Subject -> [section cells] for the entry
     * grid, with sections sorted alphabetically so the form renders in a stable
     * order across page loads.
     *
     * @param array<string,array{grade:string,subject:string,section:?string}> $cells
     * @return array<string,array<string,array<int,array{label:string,section:?string}>>>
     */
    private function sectionTree(array $cells): array
    {
        $tree = [];
        foreach ($cells as $cell) {
            $tree[$cell['grade']][$cell['subject']][] = [
                'label'   => $cell['section'] ?? '(no section on record)',
                'section' => $cell['section'] ?? self::NO_SECTION,
            ];
        }

        foreach ($tree as &$bySubject) {
            foreach ($bySubject as &$sectionCells) {
                usort($sectionCells, static fn (array $a, array $b) => strcmp($a['label'], $b['label']));
            }
        }

        return $tree;
    }

    /** Extracts every "Grade N" token (7/8/9/10) found in free text like "8 AND 10" or "8 and 9". */
    private function parseGradeTokens(?string $raw): array
    {
        if (! $raw || ! preg_match_all('/\b(7|8|9|10)\b/', $raw, $matches)) {
            return [];
        }

        return array_values(array_unique(array_map(static fn (string $n) => 'Grade ' . $n, $matches[1])));
    }

    /**
     * Parses one free-text subject-handle entry (e.g. "MAPEH 9 (5)",
     * "V.E 9 (1)", "ENGLISH (3)") into a canonical subject + optional grade.
     * Returns null when the leading code doesn't match any MPS-tracked
     * subject (HG, RESEARCH, SPFL, ...). Only used as a fallback for
     * teacher_subjects rows with no structured grade_level/section.
     *
     * @return array{subject:string,grade:?string}|null
     */
    private function parseSubjectEntry(string $raw): ?array
    {
        $clean = trim((string) preg_replace('/\([^)]*\)/', '', $raw));

        if (! preg_match('/^([A-Za-z.\-]+)/', $clean, $codeMatch)) {
            return null;
        }

        $subject = $this->resolveMpsSubject($codeMatch[1]);
        if ($subject === null) {
            return null;
        }

        $grade = null;
        if (preg_match('/\b(7|8|9|10)\b/', $clean, $gradeMatch)) {
            $grade = 'Grade ' . $gradeMatch[1];
        }

        return ['subject' => $subject, 'grade' => $grade];
    }
>>>>>>> Stashed changes
}
