<?php

namespace App\Controllers;

use App\Models\DocumentLinkModel;

class DocumentLinks extends BaseController
{
    public function index()
    {
        $model = new DocumentLinkModel();

        if ($this->request->getMethod() === 'POST' && hasRole('admin', 'secretary')) {
            $action = $this->request->getPost('action');

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
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Link added.']);
            } elseif ($action === 'delete') {
                $model->delete((int) $this->request->getPost('id'));
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Link removed.']);
            }

            return redirect()->to('/document-links');
        }

        $cat     = $this->request->getGet('category') ?? 'all';
        $builder = $model->orderBy('date_added', 'DESC');
        if ($cat !== 'all') {
            $builder->where('category', $cat);
        }

        return view('pages/document_links', [
            'pageTitle' => 'Document Links',
            'links'     => $builder->findAll(),
            'cat'       => $cat,
            'flash'     => session()->getFlashdata('flash'),
        ]);
    }
}
