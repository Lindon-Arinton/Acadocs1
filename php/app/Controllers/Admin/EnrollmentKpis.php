<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DepedKpiReportModel;
use App\Models\EnrollmentByLevelModel;
use App\Models\PerformanceByLevelModel;

class EnrollmentKpis extends BaseController
{
    // Hardcoded: the school years covered by the client's provided DepEd KPI reports.
    private const YEAR_OPTIONS = ['2023-2024', '2022-2023', '2021-2022', '2020-2021'];

    // MPS isn't reported in the DepEd KPI docs above — it lives in the same
    // performance_by_level table Performance Analytics reads from, and the
    // only school year with real scores there is 2024-2025.
    private const MPS_SOURCE_YEAR = '2024-2025';

    public function index()
    {
        $year = $this->request->getGet('year') ?? self::YEAR_OPTIONS[0];
        if (!in_array($year, self::YEAR_OPTIONS, true)) {
            $year = self::YEAR_OPTIONS[0];
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

        $mpsByTerm = (new PerformanceByLevelModel())
            ->where('school_year', self::MPS_SOURCE_YEAR)
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
            'pageTitle'      => 'Enrollment KPIs',
            'years'          => self::YEAR_OPTIONS,
            'depedKpis'      => (new DepedKpiReportModel())->allByYear(),
            'mpsTrend'       => $mpsTrend,
            'mpsSourceYear'  => self::MPS_SOURCE_YEAR,
            'mpsOverallAvg'  => $mpsOverallAvg,
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
