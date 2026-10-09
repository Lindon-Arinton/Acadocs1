<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\SchoolCalendar;
use App\Libraries\TermPack as TermPackBuilder;
use App\Models\UserModel;

/**
 * End-of-term / end-of-year report pack: enrollment, staff attendance, MPS by
 * learning area and task compliance in one printable page (Save as PDF).
 * The principal and ADAS are notified when one is ready (App\Libraries\Automation).
 */
class TermPack extends BaseController
{
    public function index()
    {
        if (! hasRole('admin', 'adas')) {
            return redirect()->to('/teacher-dashboard');
        }

        $years = array_keys(config('SchoolCalendar')->years);
        $today = (new SchoolCalendar())->on();
        $year  = (string) ($this->request->getGet('year') ?: ($today['schoolYear'] ?? end($years)));
        $term  = (string) ($this->request->getGet('term') ?: ($today['term'] ?? 'year'));

        $pack = (new TermPackBuilder())->build($year, $term);
        if ($pack === null) {
            return redirect()->to('/dashboard')->with('flash', ['type' => 'danger', 'msg' => 'That school year or term is not on the school calendar.']);
        }

        return view('pages/admin/term_pack', [
            'pack'          => $pack,
            'years'         => $years,
            'terms'         => array_keys(TermPackBuilder::termsOf($year)),
            'generatedBy'   => currentUser()['name'] ?? '',
            'principalName' => (new UserModel())->select('name')->where('role', 'admin')->orderBy('id', 'ASC')->first()['name'] ?? '',
        ]);
    }
}
