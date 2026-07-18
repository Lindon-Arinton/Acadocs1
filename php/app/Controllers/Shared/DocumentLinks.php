<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\DocumentLinkModel;

class DocumentLinks extends BaseController
{
    public function index()
    {
        $model = new DocumentLinkModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('admin', 'secretary')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/document-links');
            }

            $action  = $this->request->getPost('action');
            $message = null;

            try {
                if ($action === 'add') {
                    $model->insert([
                        'category'     => $this->request->getPost('category'),
                        'title'        => $this->request->getPost('title'),
                        'description'  => $this->request->getPost('description'),
                        'url'          => $this->request->getPost('url'),
                        'added_by'     => currentUser()['name'],
                        'date_added'   => date('Y-m-d'),
                        'access_level' => $this->request->getPost('access_level'),
                    ]);
                    $message = 'Link added.';
                } elseif ($action === 'delete') {
                    $model->delete((int) $this->request->getPost('id'));
                    $message = 'Link removed.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/document-links');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/document-links');
        }

        $cat    = $this->request->getGet('category') ?? 'all';
        $search = trim($this->request->getGet('q') ?? '');
        $sort   = $this->request->getGet('sort') ?? 'newest';

        $builder = $model;
        if ($cat !== 'all') {
            $builder->where('category', $cat);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('title', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }

        match ($sort) {
            'oldest'   => $builder->orderBy('date_added', 'ASC'),
            'title_az' => $builder->orderBy('title', 'ASC'),
            default    => $builder->orderBy('date_added', 'DESC'),
        };

        return view('pages/shared/document_links', [
            'pageTitle' => 'Document Links',
            'links'     => $builder->findAll(),
            'cat'       => $cat,
            'search'    => $search,
            'sort'      => $sort,
            'flash'     => session()->getFlashdata('flash'),
        ]);
    }
}
