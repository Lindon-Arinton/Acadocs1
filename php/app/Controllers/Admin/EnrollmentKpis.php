<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\EnrollmentKpiDocxImporter;
use App\Models\TemplateCategoryModel;
use App\Models\TemplateModel;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Metadata\Protection;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\DocProtect;
use PhpOffice\PhpWord\SimpleType\Jc;

class EnrollmentKpis extends BaseController
{
    /** Display labels for the blank template, in the order the DepEd form uses. */
    private const TEMPLATE_INDICATORS = [
        'Gross Enrolment Rate', 'Net Enrolment Rate', 'Cohort Survival Rate',
        'Repetition Rate', 'Promotion Rate', 'Retention Rate', 'Graduation Rate',
        'Completion Rate', 'Transition Rate', 'Drop Out Rate',
    ];

    private const TEMPLATE_GRADE_LEVELS = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];

    public function import()
    {
        if (! hasRole('admin')) {
            return redirect()->to('/dashboard');
        }

        $isAjax   = $this->request->isAJAX();
        $redirect = '/dashboard';

        $year = trim((string) $this->request->getPost('school_year'));
        if (! preg_match('/^(\d{4})-(\d{4})$/', $year, $m) || (int) $m[2] !== (int) $m[1] + 1) {
            $error = 'Please enter a valid school year in the format YYYY-YYYY (e.g. 2025-2026).';

            return $isAjax ? $this->ajaxError($error) : redirect()->to($redirect)->with('flash', ['type' => 'danger', 'msg' => $error]);
        }

        $redirect = '/dashboard?year=' . urlencode($year);

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
            return redirect()->to('/dashboard');
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
