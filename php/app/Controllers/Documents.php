<?php

namespace App\Controllers;

use App\Models\DocumentFeedbackModel;
use App\Models\DocumentModel;

class Documents extends BaseController
{
    public function index()
    {
        $documentModel = new DocumentModel();

        if ($this->request->getMethod() === 'POST' && hasRole('admin')) {
            $docId   = (int) $this->request->getPost('doc_id');
            $comment = trim($this->request->getPost('comment') ?? '');

            if ($docId && $comment) {
                (new DocumentFeedbackModel())->insert([
                    'document_id' => $docId,
                    'author'      => currentUser()['name'],
                    'comment'     => $comment,
                    'date'        => date('Y-m-d'),
                ]);
                $documentModel->update($docId, ['status' => 'Reviewed']);

                return redirect()->to('/documents?success=1');
            }
        }

        $statusFilter = $this->request->getGet('status') ?? 'all';
        $builder = $documentModel->select('documents.*, teachers.name AS teacher_name')
            ->join('teachers', 'teachers.id = documents.teacher_id')
            ->orderBy('documents.date_submitted', 'DESC');

        if ($statusFilter !== 'all') {
            $builder->where('documents.status', $statusFilter);
        }

        $docs = $builder->findAll();

        $statusCounts = $documentModel->statusCounts();

        return view('pages/documents', [
            'pageTitle'    => 'Manage Documents',
            'docs'         => $docs,
            'statusFilter' => $statusFilter,
            'statusCounts' => $statusCounts,
        ]);
    }
}
