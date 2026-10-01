<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Libraries\MpsCalculator;
use App\Libraries\MpsScoreImporter;
use App\Models\MpsTestScoreModel;
use App\Models\TeacherModel;
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
        'HG', 'Research', 'SPFL', 'SPFL-CM',
    ];

    /** Short form-field keys mapped to the DB's full test_period labels. */
    public const PERIOD_MAP = [
        's1'   => 'Summative Test 1',
        's2'   => 'Summative Test 2',
        'exam' => 'Term Examination',
    ];

    /**
     * Maps the free-text codes found in teachers.subject_handle-style data
     * (e.g. "MAPEH 9 (5)", "V.E 9 (1)", "MATH 9 (4)") to the fixed subject
     * vocabulary above (keys are the code with non-letters stripped, so
     * "V.E" -> "VE", "SPFL-CM" -> "SPFLCM"). A code not listed here still
     * shows up on the MPS page, under the name the teacher typed.
     */
    private const SUBJECT_ALIASES = [
        'ENGLISH'          => 'English',
        'ENG'              => 'English',
        'FILIPINO'         => 'Filipino',
        'FIL'              => 'Filipino',
        'SCIENCE'          => 'Science',
        'SCI'              => 'Science',
        'MATHEMATICS'      => 'Mathematics',
        'MATH'             => 'Mathematics',
        'AP'               => 'AP',
        'TLE'              => 'TLE',
        'MAPEH'            => 'MAPEH',
        'MUSIC'            => 'Music',
        'ARTS'             => 'Arts',
        'PE'               => 'PE',
        'HEALTH'           => 'Health',
        'ESP'              => 'ESP',
        'VE'               => 'ESP',
        'HG'               => 'HG',
        'HOMEROOMGUIDANCE' => 'HG',
        'RESEARCH'         => 'Research',
        'SPFL'             => 'SPFL',
        'SPFLCM'           => 'SPFL-CM',
    ];

    /** Matches "2025-2026" — the only shape a school year needs to satisfy now that it's freely typed rather than picked from a fixed list. */
    private const YEAR_PATTERN = '/^\d{4}-\d{4}$/';

    private const INVALID_YEAR_MESSAGE = 'School year must be in YYYY-YYYY format with consecutive years (e.g. 2026-2027) — letters are not allowed.';

    /**
     * Placeholder section label for a cell with no real section on record (an
     * Excel import, or a legacy teacher_subjects row with no section column
     * set). Never an empty string: `name="scores[...][]"` in HTML parses as a
     * numeric array index, not the string key '', which would break the
     * grade|subject|section lookup this constant is used to build.
     */
    private const NO_SECTION = '_none_';

    public function index()
    {
        if (! hasRole('teacher')) {
            return redirect()->to('/dashboard');
        }

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            $year = trim($this->request->getPost('school_year') ?? '');
            $term = (int) $this->request->getPost('term');

            if (! self::isValidYear($year)) {
                return $isAjax ? $this->ajaxError(self::INVALID_YEAR_MESSAGE) : $this->invalidYearRedirect('/performance/mps');
            }

            if (! in_array($term, self::TERM_OPTIONS, true)) {
                return $isAjax ? $this->ajaxError('Invalid term.') : redirect()->to('/performance/mps');
            }

            $redirect = '/performance/mps?year=' . urlencode($year) . '&term=' . $term;

            $rawScores    = $this->request->getPost('scores') ?? [];
            $handledCells = $this->handledCells($this->currentTeacherRow($year, $term));

            // Belt-and-suspenders: the form only ever renders inputs for the
            // signed-in teacher's own grade/subject/section cells, but a crafted
            // POST could still try to smuggle in someone else's — resolve every
            // posted value against handledCells and drop anything that isn't
            // actually theirs before it ever reaches the DB.
            $entries = [];
            foreach (self::PERIOD_MAP as $shortKey => $label) {
                foreach ($rawScores[$shortKey] ?? [] as $grade => $bySubject) {
                    foreach ($bySubject as $subject => $byLabel) {
                        foreach ($byLabel as $sectionLabel => $value) {
                            $value = trim((string) $value);
                            if ($value === '') {
                                continue;
                            }

                            $cell = $handledCells[$grade . '|' . $subject . '|' . $sectionLabel] ?? null;
                            if ($cell === null) {
                                continue;
                            }

                            $entries[] = [
                                'period'  => $label,
                                'grade'   => $grade,
                                'subject' => $subject,
                                'section' => $cell['section'],
                                'mps'     => (float) $value,
                            ];
                        }
                    }
                }
            }

            try {
                (new MpsCalculator())->saveSectionScores($year, $term, $entries);
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
        if (! self::isValidYear($year)) {
            $year = self::YEAR_OPTIONS[0];
        }

        $term = (int) ($this->request->getGet('term') ?? self::TERM_OPTIONS[0]);
        if (! in_array($term, self::TERM_OPTIONS, true)) {
            $term = self::TERM_OPTIONS[0];
        }

        $existingRows = (new MpsTestScoreModel())->forYearTerm($year, $term);

        // Keyed by the same "section label" handledCells/the form use — the real
        // section name, or NO_SECTION for a blended value with no section on record
        // (an Excel import, or a legacy teacher_subjects row) — so the view can look
        // a saved value up with exactly the label it's about to render.
        $existing = [];
        $periodByLabel = array_flip(self::PERIOD_MAP);
        foreach ($existingRows as $row) {
            $shortKey = $periodByLabel[$row['test_period']] ?? null;
            if ($shortKey === null) {
                continue;
            }
            $sectionLabel = $row['section'] ?? self::NO_SECTION;
            $existing[$shortKey][$row['grade_level']][$row['subject']][$sectionLabel] = $row['mps'];
        }

        $handledCells = $this->handledCells($this->currentTeacherRow($year, $term));

        return view('pages/teacher/performance_mps', [
            'pageTitle'   => 'Enter MPS Scores',
            'year'        => $year,
            'term'        => $term,
            'years'       => $this->availableYears(),
            'terms'       => self::TERM_OPTIONS,
            'gradeLevels' => $this->gradesFromCells($handledCells),
            'subjects'    => $this->subjectsFromCells($handledCells),
            'sectionTree' => $this->sectionTree($handledCells),
            'periods'     => self::PERIOD_MAP,
            'existing'    => $existing,
            'handledCells' => $handledCells,
            'flash'       => session()->getFlashdata('flash'),
        ]);
    }

    /**
     * A school year is valid only as digits-only "YYYY-YYYY" where the second
     * year is exactly the first plus one (e.g. 2026-2027).
     */
    private static function isValidYear(string $year): bool
    {
        if (! preg_match(self::YEAR_PATTERN, $year)) {
            return false;
        }

        [$start, $end] = array_map('intval', explode('-', $year));

        return $end === $start + 1;
    }

    private function invalidYearRedirect(string $to)
    {
        session()->setFlashdata('flash', ['type' => 'danger', 'msg' => self::INVALID_YEAR_MESSAGE]);

        return redirect()->to($to);
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

        if (! self::isValidYear($year)) {
            return $isAjax ? $this->ajaxError(self::INVALID_YEAR_MESSAGE) : $this->invalidYearRedirect($redirect);
        }

        if (! in_array($term, self::TERM_OPTIONS, true)) {
            return $isAjax ? $this->ajaxError('Invalid term.') : redirect()->to($redirect);
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
            $summary = (new MpsScoreImporter())->import(
                $uploadPath . DIRECTORY_SEPARATOR . $fileName,
                $year,
                $term,
                $this->gradeSubjectOnly($this->handledCells($this->currentTeacherRow($year, $term)))
            );
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

        $year = trim($this->request->getGet('year') ?? '');
        $term = (int) $this->request->getGet('term');
        if (! self::isValidYear($year) || ! in_array($term, self::TERM_OPTIONS, true)) {
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

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('MPS');

        $row = 1;
        foreach (self::PERIOD_MAP as $label) {
            $sheet->setCellValue("A{$row}", strtoupper($label));
            $row += 2;

            $sheet->fromArray(array_merge([null], $subjects), null, "A{$row}");
            $row++;

            foreach ($gradeLevels as $grade) {
                $sheet->setCellValue("A{$row}", strtoupper($grade));
                $row++;
            }

            $row += 2; // blank separator row before the next section
        }

        foreach (range('A', chr(ord('A') + count($subjects))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mps_template_' . uniqid() . '.xlsx';
        (new Xlsx($spreadsheet))->save($tempFile);

        return $this->response->download($tempFile, null)->setFileName('mps_scores_template.xlsx');
    }

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

    /**
     * Known codes map to their canonical name ("MATH" -> "Mathematics").
     * Anything else (a subject typed into Subject Load that isn't in
     * SUBJECTS yet) is kept under its own name instead of being dropped,
     * so every subject a teacher handles shows up on the MPS page.
     */
    private function resolveMpsSubject(string $rawCode): ?string
    {
        $code = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $rawCode));

        if (isset(self::SUBJECT_ALIASES[$code])) {
            return self::SUBJECT_ALIASES[$code];
        }

        $name = trim((string) preg_replace('/\s+/', ' ', $rawCode));
        if ($code === '' || $name === '') {
            return null;
        }

        // "statistics" -> "Statistics"; leave mixed/upper case ("STEM", "iCT") as typed.
        return $name === strtolower($name) ? ucwords($name) : $name;
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

        // Known subjects in their usual report order, then any others alphabetically.
        $extra = array_diff($found, self::SUBJECTS);
        sort($extra, SORT_NATURAL | SORT_FLAG_CASE);

        return array_values(array_merge(array_intersect(self::SUBJECTS, $found), $extra));
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
     * Unknown subjects are kept under their own name (see resolveMpsSubject).
     * Only used as a fallback for teacher_subjects rows with no structured
     * grade_level/section.
     *
     * @return array{subject:string,grade:?string}|null
     */
    private function parseSubjectEntry(string $raw): ?array
    {
        $clean = trim((string) preg_replace('/\([^)]*\)/', '', $raw));

        if (! preg_match('/^([A-Za-z.\-]+)/', $clean, $codeMatch)) {
            return null;
        }

        // Known leading code ("MAPEH 9") -> canonical name; otherwise the
        // whole entry minus grade numbers ("Earth Science 9" -> "Earth Science").
        $code    = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $codeMatch[1]));
        $subject = isset(self::SUBJECT_ALIASES[$code])
            ? self::SUBJECT_ALIASES[$code]
            : $this->resolveMpsSubject((string) preg_replace('/\b\d+\b/', '', $clean));
        if ($subject === null) {
            return null;
        }

        $grade = null;
        if (preg_match('/\b(7|8|9|10)\b/', $clean, $gradeMatch)) {
            $grade = 'Grade ' . $gradeMatch[1];
        }

        return ['subject' => $subject, 'grade' => $grade];
    }
}
