<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\EnrollmentKpiDocxImporter;
use App\Models\TemplateCategoryModel;
use App\Models\TemplateModel;

class EnrollmentKpis extends BaseController
{
    /** Bundled blank DepEd KPI report, served when no template has been uploaded. */
    private const DEFAULT_TEMPLATE = APPPATH . 'Templates/KEY-PERFORMANCE-INDICATOR.docx';

    public function import()
    {
        if (! hasRole('admin', 'adas')) {
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
        if (! hasRole('admin', 'adas')) {
            return redirect()->to('/dashboard');
        }

        // The template carries no school year — the admin is asked for it
        // when importing the filled-in report (see the Import KPI modal).
        // Prefer a real DepEd-issued .docx uploaded via Templates (Enrollment
        // category) if there is one; otherwise serve the bundled blank form.
        $uploaded = $this->findUploadedKpiTemplate();
        if ($uploaded !== null && is_file($uploaded['file_path'])) {
            return $this->response->download($uploaded['file_path'], null)->setFileName($uploaded['file_name']);
        }

        return $this->response->download(self::DEFAULT_TEMPLATE, null)->setFileName('KEY-PERFORMANCE-INDICATOR.docx');
    }

    /**
     * Finds the most recently uploaded .docx in the "Enrollment" Templates
     * category — where the real DepEd KPI report template gets uploaded —
     * so the download button can serve it instead of the bundled default.
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
}
