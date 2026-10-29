<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\Teacher\PerformanceMps;
use App\Libraries\SchoolCalendar;
use App\Models\RmaResultModel;

/**
 * The principal's RMA page (sidebar → RMA): the school-wide Rapid Mathematics
 * Assessment table for a school year and assessment period, as entered by the
 * Math teachers (Teacher\Rma).
 */
class RmaResults extends BaseController
{
    public function index()
    {
        if (! hasRole('admin')) {
            return redirect()->to('/dashboard');
        }

        $withData = array_column((new RmaResultModel())->select('school_year')->distinct()->orderBy('school_year', 'DESC')->findAll(), 'school_year');
        $years    = array_values(array_unique(array_merge($withData, PerformanceMps::YEAR_OPTIONS)));
        rsort($years);

        $year = (string) $this->request->getGet('year');
        if (! in_array($year, $years, true)) {
            // The school year in progress, else the newest one with results.
            $year = (new SchoolCalendar())->termOn()['schoolYear'] ?? ($withData[0] ?? $years[0]);
            if (! in_array($year, $years, true)) {
                array_unshift($years, $year);
            }
        }

        return view('pages/admin/rma_results', [
            'pageTitle' => 'RMA Results',
            'years'     => $years,
            'rma'       => RmaResultModel::schoolTable($year, $this->request->getGet('rma_period')),
        ]);
    }
}
