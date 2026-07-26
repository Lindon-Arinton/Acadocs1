<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\EnrollmentKpiDocxImporter;
use App\Models\DepedKpiReportModel;
use App\Models\EnrollmentByLevelModel;
use App\Models\PerformanceByLevelModel;
use App\Models\TemplateCategoryModel;
use App\Models\TemplateModel;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Metadata\Protection;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\DocProtect;
use PhpOffice\PhpWord\SimpleType\Jc;

class EnrollmentKpis extends BaseController
{
    // Hardcoded: the school years covered by the client's provided DepEd KPI reports.
    private const YEAR_OPTIONS = ['2023-2024', '2022-2023', '2021-2022', '2020-2021'];

    /** Display labels for the blank template, in the order the DepEd form uses. */
    private const TEMPLATE_INDICATORS = [
        'Gross Enrolment Rate', 'Net Enrolment Rate', 'Cohort Survival Rate',
        'Repetition Rate', 'Promotion Rate', 'Retention Rate', 'Graduation Rate',
        'Completion Rate', 'Transition Rate', 'Drop Out Rate',
    ];

    private const TEMPLATE_GRADE_LEVELS = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];

    // Fallback only: MPS isn't reported in the DepEd KPI docs above — it lives
    // in the same performance_by_level table Performance Analytics reads
    // from. The actual source year is always resolved dynamically (see
    // index() below) to whichever year has the most recent scores, so this
    // never goes stale the way a hardcoded year would.
    private const MPS_SOURCE_YEAR = '2024-2025';

    public function index()
    {
        $years = $this->availableYears();

        $year = $this->request->getGet('year') ?? $years[0];
        if (!in_array($year, $years, true)) {
            $year = $years[0];
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

        $latestMpsYear = (new PerformanceByLevelModel())->select('school_year')->orderBy('school_year', 'DESC')->first();
        $mpsSourceYear = $latestMpsYear['school_year'] ?? self::MPS_SOURCE_YEAR;

        $mpsByTerm = (new PerformanceByLevelModel())
            ->where('school_year', $mpsSourceYear)
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
            'pageTitle'        => 'Enrollment KPIs',
            'years'            => $years,
            'depedKpis'        => (new DepedKpiReportModel())->allByYear(),
            'enrollmentTotals' => $this->enrollmentTotalsByYear(),
            'mpsTrend'         => $mpsTrend,
            'mpsSourceYear'    => $mpsSourceYear,
            'mpsOverallAvg'    => $mpsOverallAvg,
        ]));
    }

    /**
     * School years available to view/import into: the hardcoded starting
     * list plus whatever years already have KPI or enrollment data, so a
     * newly imported year shows up without a code change. Newest first.
     */
    private function availableYears(): array
    {
        $fromKpi        = array_column((new DepedKpiReportModel())->allByYear(), 'school_year');
        $fromEnrollment = array_column(
            (new EnrollmentByLevelModel())->select('school_year')->distinct()->findAll(),
            'school_year'
        );

        $years = array_unique(array_merge(self::YEAR_OPTIONS, $fromKpi, $fromEnrollment));
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

    public function import()
    {
        if (! hasRole('admin')) {
            return redirect()->to('/enrollment-kpis');
        }

        $isAjax   = $this->request->isAJAX();
        $redirect = '/enrollment-kpis';

        $year = trim((string) $this->request->getPost('school_year'));
        if (! preg_match('/^(\d{4})-(\d{4})$/', $year, $m) || (int) $m[2] !== (int) $m[1] + 1) {
            $error = 'Please enter a valid school year in the format YYYY-YYYY (e.g. 2025-2026).';

            return $isAjax ? $this->ajaxError($error) : redirect()->to($redirect)->with('flash', ['type' => 'danger', 'msg' => $error]);
        }

        $redirect = '/enrollment-kpis?year=' . urlencode($year);

        $file = $this->request->getFile('import_file');

        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return $isAjax ? $this->ajaxError('Please choose a valid Word file to upload.') : redirect()->to($redirect);
        }

        if (strtolower($file->getExtension() ?: '') !== 'docx') {
            return $isAjax ? $this->ajaxError('Only .docx files are supported.') : redirect()->to($redirect);
        }

        $uploadPath = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'kpi_report_imports';
        if (! is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $fileName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientName());

        if (! $file->move($uploadPath, $fileName)) {
            return $isAjax ? $this->ajaxError('Could not save the uploaded file.') : redirect()->to($redirect);
        }

        try {
            $summary = (new EnrollmentKpiDocxImporter())->import($uploadPath . DIRECTORY_SEPARATOR . $fileName, $year);
        } catch (\Throwable $e) {
            return $isAjax ? $this->ajaxError('Import failed: ' . $e->getMessage()) : redirect()->to($redirect);
        }

        if ($summary['errors'] !== []) {
            $message = implode(' ', $summary['errors']);

            if ($isAjax) {
                return $this->ajaxError($message);
            }

            session()->setFlashdata('flash', ['type' => 'danger', 'msg' => $message]);

            return redirect()->to($redirect);
        }

        $message = sprintf('Imported %d KPI indicator(s) for SY %s.', count($summary['matched']), $year);

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
        if (! hasRole('admin')) {
            return redirect()->to('/enrollment-kpis');
        }

        // Prefer a real DepEd-issued .docx uploaded via Templates (Enrollment
        // category) — guarantees exact letterhead/pagination fidelity. Only
        // fall back to generating an approximation if nothing's been uploaded.
        $uploaded = $this->findUploadedKpiTemplate();
        if ($uploaded !== null && is_file($uploaded['file_path'])) {
            return $this->response->download($uploaded['file_path'], null)->setFileName($uploaded['file_name']);
        }

        $center = ['alignment' => Jc::CENTER];

        $phpWord = new PhpWord();
        // Whole document is locked; the permission range added below (the whole
        // body) is the one exception, so only the header stays read-only. Real
        // Word headers/footers are separate document parts, so putting the
        // letterhead in an actual header (rather than body text styled to look
        // like one) keeps it out of the body's page flow and repeats it on any
        // page the form overflows onto.
        $phpWord->getSettings()->setDocumentProtection(new Protection(DocProtect::READ_ONLY));

        $section = $phpWord->addSection();

        $header = $section->addHeader();
        $header->addText('Republic of the Philippines', ['bold' => true, 'size' => 12], $center);
        $header->addText('Department of Education', ['bold' => true, 'size' => 22], $center);
        $header->addText('REGION IV-A, CALABARZON', ['bold' => true, 'size' => 11], $center);
        $header->addText('SCHOOLS DIVISION OF BATANGAS PROVINCE', ['bold' => true, 'size' => 11], $center);
        $header->addText('MATABUNGKAY NATIONAL HIGH SCHOOL', ['bold' => true, 'size' => 11], $center);
        $header->addText('MATABUNGKAY, LIAN, BATANGAS', ['bold' => true, 'size' => 11], $center);

        $section->addTextBreak(1);
        $section->addText('KEY PERFORMANCE INDICATOR', ['bold' => true, 'size' => 12], $center);
        $section->addText('(School Year)', ['bold' => true, 'size' => 12], $center);
        $section->addTextBreak(1);

        $indicatorTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
        $indicatorTable->addRow();
        $indicatorTable->addCell(9000, ['gridSpan' => 2])->addText('Indicator', ['bold' => true], $center);

        foreach (self::TEMPLATE_INDICATORS as $label) {
            $indicatorTable->addRow();
            $indicatorTable->addCell(4500)->addText($label, ['bold' => true]);
            $indicatorTable->addCell(4500)->addText('');
        }

        $section->addTextBreak(1);
        $section->addText('Enrolment per Grade Level', ['bold' => true], $center);

        $gradeTable = $section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
        $gradeTable->addRow();
        foreach (['Grade Level', 'Total'] as $header) {
            $gradeTable->addCell(4500)->addText($header, ['bold' => true], $center);
        }
        foreach (self::TEMPLATE_GRADE_LEVELS as $grade) {
            $gradeTable->addRow();
            $gradeTable->addCell(4500)->addText($grade, ['bold' => true]);
            $gradeTable->addCell(4500)->addText('');
        }

        $section->addTextBreak(3);
        $section->addText('Prepared:');
        $section->addTextBreak(2);
        $section->addText('Guidance Designate');

        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'kpi_report_template_' . uniqid() . '.docx';
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);
        $this->exemptBodyFromProtection($tempFile);

        return $this->response->download($tempFile, null)->setFileName('deped_kpi_report_template.docx');
    }

    /**
     * Finds the most recently uploaded .docx in the "Enrollment" Templates
     * category — where the real DepEd KPI report template gets uploaded —
     * so the download button can serve the actual file instead of a
     * PhpWord-generated approximation.
     */
    private function findUploadedKpiTemplate(): ?array
    {
        $category = (new TemplateCategoryModel())->where('name', 'Enrollment')->first();
        if ($category === null) {
            return null;
        }

        return (new TemplateModel())
            ->where('category_id', $category['id'])
            ->where('file_ext', 'docx')
            ->orderBy('date_added', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
    }

    /**
     * The document is fully read-only protected; this marks the entire body
     * (title, both tables, signature block) as a Word "editing permission"
     * exception. The letterhead lives in a real header part (word/header1.xml),
     * a separate part this exception never touches, so it stays locked.
     */
    private function exemptBodyFromProtection(string $docxPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($docxPath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();

            return;
        }

        $sectPrPos = strpos($xml, '<w:sectPr');
        if (strpos($xml, '<w:body>') !== false && $sectPrPos !== false) {
            $xml = substr_replace($xml, '<w:permEnd w:id="100"/>', $sectPrPos, 0);
            $xml = str_replace('<w:body>', '<w:body><w:permStart w:id="100" w:edGrp="everyone"/>', $xml);

            $zip->deleteName('word/document.xml');
            $zip->addFromString('word/document.xml', $xml);
        }

        $zip->close();
    }
}
