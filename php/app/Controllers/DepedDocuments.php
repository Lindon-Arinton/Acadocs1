<?php

namespace App\Controllers;

use App\Models\DepedDocumentModel;

class DepedDocuments extends BaseController
{
    public function index()
    {
        $model = new DepedDocumentModel();

        if ($this->request->getMethod() === 'POST' && hasRole('admin', 'adas')) {
            $action = $this->request->getPost('action');

            if ($action === 'add') {
                $model->insert([
                    'document_type'   => $this->request->getPost('document_type'),
                    'description'     => $this->request->getPost('description'),
                    'due_date'        => $this->request->getPost('due_date'),
                    'status'          => $this->request->getPost('status'),
                    'completion_rate' => (int) $this->request->getPost('completion_rate'),
                    'prepared_by'     => currentUser()['name'],
                    'last_updated'    => date('Y-m-d'),
                ]);
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Document added.']);
            } elseif ($action === 'update') {
                $rate   = (int) $this->request->getPost('completion_rate');
                $status = $this->request->getPost('status');
                if ($rate >= 100) {
                    $status = 'Completed';
                }

                $model->update((int) $this->request->getPost('id'), [
                    'status'          => $status,
                    'completion_rate' => $rate,
                    'last_updated'    => date('Y-m-d'),
                ]);
                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Document progress updated.']);
            }

            return redirect()->to('/deped-documents');
        }

        $docs = $model->orderBy('due_date', 'ASC')->findAll();

        $statusCount = ['Completed' => 0, 'In Progress' => 0, 'Pending' => 0];
        foreach ($docs as $d) {
            if (isset($statusCount[$d['status']])) {
                $statusCount[$d['status']]++;
            }
        }

        return view('pages/deped_documents', [
            'pageTitle'   => 'DepEd Documents',
            'docs'        => $docs,
            'statusCount' => $statusCount,
            'flash'       => session()->getFlashdata('flash'),
        ]);
    }
}
