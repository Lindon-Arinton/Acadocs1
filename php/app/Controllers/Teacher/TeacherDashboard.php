<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;
use App\Models\DocumentLinkModel;
use App\Models\DocumentModel;
use App\Models\TeacherModel;

class TeacherDashboard extends BaseController
{
    public function index()
    {
        $user = currentUser();
        $teacherModel = new TeacherModel();
        $teacher = $teacherModel->resolveForUser($user);

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
            'stats'         => $stats,
            'announcements' => $announcements,
            'links'         => $links,
        ]);
    }
}
