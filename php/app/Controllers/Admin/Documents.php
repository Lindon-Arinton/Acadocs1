<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DocumentFolderModel;
use App\Models\NotificationModel;
use App\Models\TaskFeedbackModel;
use App\Models\TaskModel;
use App\Models\TaskSubmissionFileModel;
use App\Models\TaskSubmissionModel;
use App\Models\UserModel;

/**
 * Document Management: one folder per task (auto-created on the first
 * upload), where admins review each person's upload.
 */
class Documents extends BaseController
{
    // Uploads start as Pending; these are the decisions an admin can make.
    private const REVIEW_STATUSES = ['Reviewed', 'Returned'];

    public function index()
    {
        $folderId = (int) ($this->request->getGet('folder') ?? 0);

        if ($this->request->getMethod() === 'POST') {
            return $this->review($folderId);
        }

        if ($folderId) {
            return $this->folder($folderId);
        }

        $search = trim($this->request->getGet('q') ?? '');

        return view('pages/admin/documents', [
            'pageTitle' => 'Manage Documents',
            'folders'   => (new DocumentFolderModel())->withStats($search),
            'search'    => $search,
        ]);
    }

    /**
     * A task folder: every submitter's upload for that task.
     */
    private function folder(int $folderId)
    {
        $folder = (new DocumentFolderModel())->find($folderId);
        $task   = $folder ? (new TaskModel())->find($folder['task_id']) : null;

        if (! $folder || ! $task) {
            return redirect()->to('/documents');
        }

        $submissions = (new TaskSubmissionModel())
            ->select('task_submissions.*, users.name AS submitter_name')
            ->join('users', 'users.id = task_submissions.user_id')
            ->where('task_submissions.task_id', $task['id'])
            ->orderBy('task_submissions.submitted_at', 'DESC')
            ->findAll();

        $submissionIds = array_column($submissions, 'id');
        $filesGrouped  = (new TaskSubmissionFileModel())->forSubmissions($submissionIds);

        $feedbackGrouped = [];
        if ($submissionIds) {
            $feedbackRows = (new TaskFeedbackModel())->whereIn('task_submission_id', $submissionIds)
                ->orderBy('id', 'DESC')->findAll();
            foreach ($feedbackRows as $row) {
                $feedbackGrouped[$row['task_submission_id']][] = $row;
            }
        }

        foreach ($submissions as &$submission) {
            $submission['files']    = $filesGrouped[$submission['id']] ?? [];
            $submission['feedback'] = $feedbackGrouped[$submission['id']] ?? [];
        }
        unset($submission);

        return view('pages/admin/document_folder', [
            'pageTitle'   => $folder['name'],
            'folder'      => $folder,
            'task'        => $task,
            'submissions' => $submissions,
            'flash'       => session()->getFlashdata('flash'),
        ]);
    }

    /**
     * Marks one upload in the folder Reviewed or Returned. A comment
     * is required when returning it so the submitter knows what to fix.
     */
    private function review(int $folderId)
    {
        $isAjax  = $this->request->isAJAX();
        $backUrl = '/documents' . ($folderId ? '?folder=' . $folderId : '');

        if (! hasRole('admin', 'adas')) {
            return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to($backUrl);
        }

        $submissionModel = new TaskSubmissionModel();
        $submissionId    = (int) $this->request->getPost('submission_id');
        $status          = $this->request->getPost('status');
        $comment         = trim($this->request->getPost('comment') ?? '');

        $submission = $submissionModel->find($submissionId);
        $folder     = (new DocumentFolderModel())->find($folderId);

        if (! $submission || ! $folder || (int) $folder['task_id'] !== (int) $submission['task_id']
            || ! in_array($status, self::REVIEW_STATUSES, true)) {
            return $isAjax ? $this->ajaxError('Invalid review request.') : redirect()->to($backUrl);
        }

        if ($status === 'Returned' && $comment === '') {
            $error = 'Please say what needs to be fixed before returning it.';

            return $isAjax ? $this->ajaxError($error) : redirect()->to($backUrl)->with('flash', ['type' => 'danger', 'msg' => $error]);
        }

        try {
            $submissionModel->update($submissionId, ['status' => $status]);

            if ($comment !== '') {
                (new TaskFeedbackModel())->insert([
                    'task_submission_id' => $submissionId,
                    'comment'            => $comment,
                    'date'               => date('Y-m-d'),
                ]);
            }

            $task      = (new TaskModel())->find($submission['task_id']);
            $submitter = (new UserModel())->find((int) $submission['user_id']);
            $headline  = [
                'Reviewed' => 'Your upload was reviewed: ',
                'Returned' => 'Your upload was returned: ',
            ][$status];

            (new NotificationModel())->upsertGrouped(
                (int) $submission['user_id'],
                'task_feedback',
                $submissionId,
                'task_feedback',
                $headline . ($task['title'] ?? 'Task'),
                $comment !== '' ? mb_strimwidth($comment, 0, 80, '…') : 'Status: ' . $status,
                base_url(($submitter['role'] ?? null) === 'teacher' ? 'submit-documents' : 'my-tasks')
            );
        } catch (\Throwable $e) {
            return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to($backUrl);
        }

        $message = 'Marked as ' . $status . '.';

        if ($isAjax) {
            return $this->ajaxSuccess($message);
        }

        session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message]);

        return redirect()->to($backUrl);
    }
}
