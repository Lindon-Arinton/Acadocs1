<?php

namespace App\Controllers;

use App\Models\AnnouncementModel;
use App\Models\DocumentLinkModel;

class SecretaryDashboard extends BaseController
{
    public function index()
    {
        $user = currentUser();

        $announcementModel = new AnnouncementModel();
        $announcements     = $announcementModel->where('status', 'active')->orderBy('date', 'DESC')->findAll(5);
        $announcementCount = $announcementModel->where('status', 'active')->countAllResults();

        $linkModel = new DocumentLinkModel();
        $links     = $linkModel->orderBy('date_added', 'DESC')->findAll(6);
        $linkCount = $linkModel->countAllResults();

        return view('pages/secretary_dashboard', [
            'pageTitle'         => 'Secretary Dashboard',
            'user'              => $user,
            'announcements'     => $announcements,
            'announcementCount' => $announcementCount,
            'links'             => $links,
            'linkCount'         => $linkCount,
        ]);
    }
}
