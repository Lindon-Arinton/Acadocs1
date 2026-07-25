<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use App\Models\TaskFeedbackModel;
use App\Models\TaskModel;
use App\Models\TaskSubmissionFileModel;
use App\Models\TaskSubmissionModel;
use App\Models\UserModel;

class MyTasks extends BaseController
{
    public function index()
    {
        if (! hasRole('teacher', 'adas')) {
            return redirect()->to('/dashboard');
        }

        $user             = currentUser();
        $taskModel        = new TaskModel();
        $submissionModel  = new TaskSubmissionModel();
        $feedbackModel    = new TaskFeedbackModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax = $this->request->isAJAX();
            $taskId = (int) $this->request->getPost('task_id');
            $task   = $taskModel->find($taskId);

            if (! $task || $task['assigned_role'] !== $user['role']) {
                return $isAjax ? $this->ajaxError('You are not authorized to do this.', 403) : redirect()->to('/my-tasks');
            }

            $files = array_filter(
                $this->request->getFileMultiple('file') ?? [],
                static fn ($f) => $f && $f->isValid() && ! $f->hasMoved()
            );

            if (empty($files)) {
                $error = 'Please choose at least one file to upload.';

                return $isAjax ? $this->ajaxError($error) : redirect()->to('/my-tasks')->with('flash', ['type' => 'danger', 'msg' => $error]);
            }

            $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
            foreach ($files as $f) {
                if ($f->getSize() > 10240 * 1024) {
                    $error = 'Each file must be 10MB or smaller.';

                    return $isAjax ? $this->ajaxError($error) : redirect()->to('/my-tasks')->with('flash', ['type' => 'danger', 'msg' => $error]);
                }
                if (! in_array(strtolower($f->getClientExtension()), $allowedExt, true)) {
                    $error = 'Unsupported file type: ' . $f->getClientName();

                    return $isAjax ? $this->ajaxError($error) : redirect()->to('/my-tasks')->with('flash', ['type' => 'danger', 'msg' => $error]);
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
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/my-tasks');
            }

            if ($isAjax) {
                return $this->ajaxSuccess('Document submitted successfully.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Document submitted successfully.']);

            return redirect()->to('/my-tasks');
        }

        $tasks     = $taskModel->where('assigned_role', $user['role'])->orderBy('deadline', 'ASC')->findAll();
        $fileModel = new TaskSubmissionFileModel();

        foreach ($tasks as &$task) {
            $submission          = $submissionModel->findForTaskAndUser($task['id'], (int) $user['id']);
            $task['submission']  = $submission;
            $task['files']       = $submission ? $fileModel->forSubmission($submission['id']) : [];
            $task['feedback']    = $submission
                ? $feedbackModel->where('task_submission_id', $submission['id'])->orderBy('date', 'DESC')->findAll()
                : [];
        }
        unset($task);

        return view('pages/shared/my_tasks', [
            'pageTitle' => 'My Tasks',
            'tasks'     => $tasks,
            'flash'     => session()->getFlashdata('flash'),
        ]);
    }
}
