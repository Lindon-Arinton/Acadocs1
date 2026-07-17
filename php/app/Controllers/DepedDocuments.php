<?php

namespace App\Controllers;

use App\Models\DepedDocumentModel;

class DepedDocuments extends BaseController
{
    public function index()
    {
        $model = new DepedDocumentModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('admin', 'adas')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/deped-documents');
            }

            $action  = $this->request->getPost('action');
            $message = null;

            try {
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
                    $message = 'Document added.';
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
                    $message = 'Document progress updated.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/deped-documents');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

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
