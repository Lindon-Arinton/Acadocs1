<?php

namespace App\Controllers;

use App\Models\AnnouncementModel;

class Announcements extends BaseController
{
    public function index()
    {
        $model = new AnnouncementModel();

        if ($this->request->getMethod() === 'POST' && hasRole('admin', 'secretary')) {
            $action = $this->request->getPost('action');

            if ($action === 'add') {
                $model->insert([
                    'type'    => $this->request->getPost('type'),
                    'title'   => $this->request->getPost('title'),
                    'content' => $this->request->getPost('content'),
                    'date'    => $this->request->getPost('date'),
                    'status'  => 'active',
                ]);
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Announcement posted successfully.']);
            } elseif ($action === 'delete') {
                $model->delete((int) $this->request->getPost('id'));
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Announcement deleted.']);
            }

            return redirect()->to('/announcements');
        }

        $filter = $this->request->getGet('type') ?? 'all';
        $builder = $model->orderBy('date', 'DESC');
        if ($filter !== 'all') {
            $builder->where('type', $filter);
        }

        return view('pages/announcements', [
            'pageTitle'     => 'Announcements',
            'announcements' => $builder->findAll(),
            'filter'        => $filter,
            'flash'         => session()->getFlashdata('flash'),
        ]);
    }
}
