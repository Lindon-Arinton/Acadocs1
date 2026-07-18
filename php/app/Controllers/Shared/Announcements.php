<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\AnnouncementModel;

class Announcements extends BaseController
{
    public function index()
    {
        $model = new AnnouncementModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('admin', 'secretary')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/announcements');
            }

            $action  = $this->request->getPost('action');
            $message = null;

            try {
                if ($action === 'add') {
                    $model->insert([
                        'type'    => $this->request->getPost('type'),
                        'title'   => $this->request->getPost('title'),
                        'content' => $this->request->getPost('content'),
                        'date'    => $this->request->getPost('date'),
                        'status'  => 'active',
                    ]);
                    $message = 'Announcement posted successfully.';
                } elseif ($action === 'delete') {
                    $model->delete((int) $this->request->getPost('id'));
                    $message = 'Announcement deleted.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/announcements');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/announcements');
        }

        $filter = $this->request->getGet('type') ?? 'all';
        $search = trim($this->request->getGet('q') ?? '');
        $sort   = $this->request->getGet('sort') ?? 'newest';

        $builder = $model;
        if ($filter !== 'all') {
            $builder->where('type', $filter);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('content', $search)
                ->groupEnd();
        }

        match ($sort) {
            'oldest'   => $builder->orderBy('date', 'ASC'),
            'title_az' => $builder->orderBy('title', 'ASC'),
            default    => $builder->orderBy('date', 'DESC'),
        };

        return view('pages/shared/announcements', [
            'pageTitle'     => 'Announcements',
            'announcements' => $builder->findAll(),
            'filter'        => $filter,
            'search'        => $search,
            'sort'          => $sort,
            'flash'         => session()->getFlashdata('flash'),
        ]);
    }
}
