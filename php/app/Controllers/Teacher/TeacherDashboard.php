<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;
use App\Models\DocumentLinkModel;
use App\Models\DocumentModel;
use App\Models\TeacherModel;
use App\Models\TimeRecordModel;

class TeacherDashboard extends BaseController
{
    public function index()
    {
        $user = currentUser();
        $teacherModel = new TeacherModel();
        $teacher = $teacherModel->resolveForUser($user);

        $myAttendance       = ['Present' => 0, 'Absent' => 0];
        $myAttendanceMonths = [];
        $attMonth           = $this->request->getGet('att_month') ?: 'all';

        if (! empty($user['ac_no'])) {
            $trModel = new TimeRecordModel();

            $myAttendanceMonths = array_column(
                $trModel->select("DATE_FORMAT(date, '%Y-%m') as ym", false)
                    ->where('employee_id', 'AC-' . $user['ac_no'])
                    ->distinct()
                    ->orderBy('ym', 'DESC')
                    ->findAll(),
                'ym'
            );

            if ($attMonth !== 'all' && ! in_array($attMonth, $myAttendanceMonths, true)) {
                $attMonth = 'all';
            }

            $query = $trModel->where('employee_id', 'AC-' . $user['ac_no']);
            if ($attMonth !== 'all') {
                $query->where('date >=', $attMonth . '-01')
                    ->where('date <=', date('Y-m-t', strtotime($attMonth . '-01')));
            }

            foreach ($query->findAll() as $r) {
                if (isset($myAttendance[$r['status']])) {
                    $myAttendance[$r['status']]++;
                }
            }
        }

        $documentModel = new DocumentModel();
        $myDocs = [];
        $stats  = ['Submitted' => 0, 'Reviewed' => 0, 'Pending' => 0];

        if ($teacher) {
            $myDocs = $documentModel->where('teacher_id', $teacher['id'])
                ->orderBy('date_submitted', 'DESC')->findAll(10);

            foreach ($documentModel->statusCounts($teacher['id']) as $status => $cnt) {
                if (isset($stats[$status])) {
                    $stats[$status] = $cnt;
                }
            }
        }

        $announcements = (new AnnouncementModel())->where('status', 'active')->orderBy('date', 'DESC')->findAll(5);
        $links = (new DocumentLinkModel())->whereIn('access_level', ['All Users', 'Teachers'])
            ->orderBy('date_added', 'DESC')->findAll(6);

        return view('pages/teacher/teacher_dashboard', [
            'pageTitle'     => 'Teacher Dashboard',
            'user'          => $user,
            'teacher'       => $teacher,
            'myDocs'        => $myDocs,
            'stats'              => $stats,
            'myAttendance'       => $myAttendance,
            'myAttendanceMonths' => $myAttendanceMonths,
            'attMonth'           => $attMonth,
            'announcements'      => $announcements,
            'links'              => $links,
        ]);
    }
}
