<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DocumentFeedbackModel;
use App\Models\DocumentModel;
use App\Models\NotificationModel;

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

            $docId    = (int) $this->request->getPost('doc_id');
            $comment  = trim($this->request->getPost('comment') ?? '');
            $decision = $this->request->getPost('decision') === 'reject' ? 'reject' : 'approve';

            if ($docId && $comment) {
                try {
                    (new DocumentFeedbackModel())->insert([
                        'document_id' => $docId,
                        'author'      => currentUser()['name'],
                        'comment'     => $comment,
                        'date'        => date('Y-m-d'),
                    ]);
                    $documentModel->update($docId, ['status' => $decision === 'reject' ? 'Returned' : 'Reviewed']);

                    $doc = $documentModel->select('documents.*, teachers.user_id AS teacher_user_id')
                        ->join('teachers', 'teachers.id = documents.teacher_id')
                        ->find($docId);

                    if ($doc && $doc['teacher_user_id']) {
                        (new NotificationModel())->upsertGrouped(
                            (int) $doc['teacher_user_id'],
                            'document_feedback',
                            $docId,
                            'document_feedback',
                            'New feedback on your ' . $doc['type'] . ' submission',
                            mb_strimwidth($comment, 0, 80, '…'),
                            base_url('submit-documents')
                        );
                    }
                } catch (\Throwable $e) {
                    return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/documents');
                }

                $message = $decision === 'reject' ? 'Document rejected and returned to teacher.' : 'Feedback submitted successfully.';

                if ($isAjax) {
                    return $this->ajaxSuccess($message);
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
