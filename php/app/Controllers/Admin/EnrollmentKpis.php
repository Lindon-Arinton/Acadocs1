<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\EnrollmentKpiDocxImporter;
use App\Models\EnrollmentByLevelModel;
use App\Models\KpiReportIndicatorModel;
use App\Models\KpiSnapshotModel;

class EnrollmentKpis extends BaseController
{
    // Hardcoded: current school year plus the 3 preceding ones.
    public const YEAR_OPTIONS = ['2026-2027', '2025-2026', '2024-2025', '2023-2024', '2022-2023'];

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
            'kpiReport'  => (new KpiReportIndicatorModel())->forYear($year) ?? [],
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

    public function import()
    {
        if (! hasRole('admin')) {
            return redirect()->to('/enrollment-kpis');
        }

        $isAjax   = $this->request->isAJAX();
        $redirect = '/enrollment-kpis';

        $year = $this->request->getPost('school_year');
        if (! in_array($year, self::YEAR_OPTIONS, true)) {
            return $isAjax ? $this->ajaxError('Invalid school year.') : redirect()->to($redirect);
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
}
