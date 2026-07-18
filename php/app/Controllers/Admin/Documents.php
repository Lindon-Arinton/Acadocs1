<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DocumentFeedbackModel;
use App\Models\DocumentModel;

class Documents extends BaseController
{
    public function index()
    {
        $documentModel = new DocumentModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();

            if (! hasRole('admin')) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/documents');
            }

            $docId   = (int) $this->request->getPost('doc_id');
            $comment = trim($this->request->getPost('comment') ?? '');

            if ($docId && $comment) {
                try {
                    (new DocumentFeedbackModel())->insert([
                        'document_id' => $docId,
                        'author'      => currentUser()['name'],
                        'comment'     => $comment,
                        'date'        => date('Y-m-d'),
                    ]);
                    $documentModel->update($docId, ['status' => 'Reviewed']);
                } catch (\Throwable $e) {
                    return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/documents');
                }

                if ($isAjax) {
                    return $this->ajaxSuccess('Feedback submitted successfully.');
                }

                return redirect()->to('/documents?success=1');
            }

            if ($isAjax) {
                return $this->ajaxError('Please enter a comment before submitting.');
            }
        }

        $statusFilter = $this->request->getGet('status') ?? 'all';
        $search       = trim($this->request->getGet('q') ?? '');
        $sort         = $this->request->getGet('sort') ?? 'date_desc';

        $builder = $documentModel->select('documents.*, teachers.name AS teacher_name')
            ->join('teachers', 'teachers.id = documents.teacher_id');

        if ($statusFilter !== 'all') {
            $builder->where('documents.status', $statusFilter);
        }
        if ($search !== '') {
            $builder->groupStart()
                ->like('teachers.name', $search)
                ->orLike('documents.subject', $search)
                ->orLike('documents.type', $search)
                ->orLike('documents.grade_level', $search)
                ->groupEnd();
        }

        match ($sort) {
            'date_asc'   => $builder->orderBy('documents.date_submitted', 'ASC'),
            'teacher_az' => $builder->orderBy('teachers.name', 'ASC'),
            'status'     => $builder->orderBy('documents.status', 'ASC'),
            default      => $builder->orderBy('documents.date_submitted', 'DESC'),
        };

        $docs = $builder->findAll();

        $statusCounts = $documentModel->statusCounts();

        return view('pages/admin/documents', [
            'pageTitle'    => 'Manage Documents',
            'docs'         => $docs,
            'statusFilter' => $statusFilter,
            'statusCounts' => $statusCounts,
            'search'       => $search,
            'sort'         => $sort,
        ]);
    }
}
