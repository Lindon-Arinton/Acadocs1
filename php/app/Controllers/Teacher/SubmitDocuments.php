<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use App\Models\TaskAssigneeModel;
use App\Models\TaskFeedbackModel;
use App\Models\TaskModel;
use App\Models\TaskSubmissionFileModel;
use App\Models\TaskSubmissionModel;
use App\Models\UserModel;

/**
 * Teacher's "To Do List" — assigned tasks + submissions against them (the
 * same task_submissions mechanism ADAS uses on Shared\MyTasks). The route
 * stays /submit-documents (existing bookmarks/notifications keep working);
 * the formal documents/document_feedback review workflow that used to live
 * here as a separate tab has been retired for teachers — that table and the
 * principal-facing Manage Documents page are untouched for historical data.
 */
class SubmitDocuments extends BaseController
{
    public function index()
    {
        if (! hasRole('teacher')) {
            return redirect()->to('/dashboard');
        }

        $user = currentUser();

        if ($this->request->getMethod() === 'POST') {
            return $this->submitTask($user);
        }

        $specificTaskIds = (new TaskAssigneeModel())->taskIdsForUser((int) $user['id']);

        return view('pages/teacher/submit_documents', [
            'pageTitle' => 'To Do List',
            'myTasks'   => $this->myTasksList($user, $specificTaskIds),
            'flash'     => session()->getFlashdata('flash'),
        ]);
    }

    /**
     * Ported from Shared\MyTasks::index()'s POST branch, unchanged — this is
     * the same generic notes+files task_submissions mechanism ADAS still
     * uses on that page.
     */
    private function submitTask(array $user)
    {
        $isAjax          = $this->request->isAJAX();
        $taskModel       = new TaskModel();
        $submissionModel = new TaskSubmissionModel();
        $assigneeModel   = new TaskAssigneeModel();

        $taskId = (int) $this->request->getPost('task_id');
        $task   = $taskModel->find($taskId);

        $isEligible = $task && (
            $task['assigned_role'] === $user['role']
            || ($task['assigned_role'] === 'specific' && in_array((int) $user['id'], $assigneeModel->userIdsForTask($taskId), true))
        );

        if (! $isEligible) {
            return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/submit-documents');
        }

        $files = array_filter(
            $this->request->getFileMultiple('file') ?? [],
            static fn ($f) => $f && $f->isValid() && ! $f->hasMoved()
        );

        if (empty($files)) {
            $error = 'Please choose at least one file to upload.';

            return $isAjax ? $this->ajaxError($error) : redirect()->to('/submit-documents')->with('flash', ['type' => 'danger', 'msg' => $error]);
        }

        $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
        foreach ($files as $f) {
            if ($f->getSize() > 10240 * 1024) {
                $error = 'Each file must be 10MB or smaller.';

                return $isAjax ? $this->ajaxError($error) : redirect()->to('/submit-documents')->with('flash', ['type' => 'danger', 'msg' => $error]);
            }
            if (! in_array(strtolower($f->getClientExtension()), $allowedExt, true)) {
                $error = 'Unsupported file type: ' . $f->getClientName();

                return $isAjax ? $this->ajaxError($error) : redirect()->to('/submit-documents')->with('flash', ['type' => 'danger', 'msg' => $error]);
            }
        }

        $fileModel = new TaskSubmissionFileModel();

        try {
            $existing  = $submissionModel->findForTaskAndUser($taskId, (int) $user['id']);
            $targetDir = WRITEPATH . 'uploads/tasks/' . $taskId;

            if (! is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $data = [
                'task_id'      => $taskId,
                'user_id'      => $user['id'],
                'notes'        => $this->request->getPost('notes') ?? '',
                'status'       => 'Submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                foreach ($fileModel->forSubmission($existing['id']) as $oldFile) {
                    if (is_file($oldFile['file_path'])) {
                        unlink($oldFile['file_path']);
                    }
                    $fileModel->delete($oldFile['id']);
                }
                $submissionModel->update($existing['id'], $data);
                $submissionId = $existing['id'];
            } else {
                $submissionId = $submissionModel->insert($data);
            }

            foreach ($files as $f) {
                $newName = $f->getRandomName();
                $f->move($targetDir, $newName);

                $fileModel->insert([
                    'task_submission_id' => $submissionId,
                    'file_path'           => $targetDir . DIRECTORY_SEPARATOR . $newName,
                    'file_name'           => $f->getClientName(),
                ]);
            }

            $totalSubmitted = $submissionModel->where('task_id', $taskId)->countAllResults();
            $others         = $totalSubmitted - 1;
            $notifTitle     = $user['name'] . ($others > 0
                ? ' and ' . $others . ' other' . ($others > 1 ? 's' : '')
                : '') . ' submitted';

            $notifModel = new NotificationModel();
            foreach ((new UserModel())->where('role', 'admin')->findAll() as $admin) {
                $notifModel->upsertGrouped(
                    (int) $admin['id'],
                    'task_submission',
                    $taskId,
                    'task_submission',
                    $notifTitle,
                    'Task: ' . $task['title'],
                    base_url('tasks/' . $taskId)
                );
            }
        } catch (\Throwable $e) {
            return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/submit-documents');
        }

        if ($isAjax) {
            return $this->ajaxSuccess('Document submitted successfully.');
        }

        session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Document submitted successfully.']);

        return redirect()->to('/submit-documents');
    }

    /**
     * Ported from Shared\MyTasks::index()'s GET branch, unchanged.
     *
     * @return array<int,array<string,mixed>>
     */
    private function myTasksList(array $user, array $specificTaskIds): array
    {
        $taskModel       = new TaskModel();
        $submissionModel = new TaskSubmissionModel();
        $feedbackModel   = new TaskFeedbackModel();
        $fileModel       = new TaskSubmissionFileModel();

        $taskQuery = $taskModel->groupStart()->where('assigned_role', $user['role']);
        if ($specificTaskIds) {
            $taskQuery->orGroupStart()->where('assigned_role', 'specific')->whereIn('id', $specificTaskIds)->groupEnd();
        }
        $tasks = $taskQuery->groupEnd()->orderBy('deadline', 'ASC')->findAll();

        foreach ($tasks as &$task) {
            $submission         = $submissionModel->findForTaskAndUser($task['id'], (int) $user['id']);
            $task['submission'] = $submission;
            $task['files']      = $submission ? $fileModel->forSubmission($submission['id']) : [];
            $task['feedback']   = $submission
                ? $feedbackModel->where('task_submission_id', $submission['id'])->orderBy('date', 'DESC')->findAll()
                : [];
        }
        unset($task);

        return $tasks;
    }
}
