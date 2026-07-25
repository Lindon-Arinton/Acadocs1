<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use App\Models\TaskFeedbackModel;
use App\Models\TaskModel;
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

            $file = $this->request->getFile('file');

            $rules = [
                'file' => [
                    'label' => 'File',
                    'rules' => 'uploaded[file]|max_size[file,10240]|ext_in[file,pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png]',
                    'errors' => [
                        'uploaded' => 'Please choose a file to upload.',
                        'max_size' => 'File is too large (max 10MB).',
                        'ext_in'   => 'Unsupported file type.',
                    ],
                ],
            ];

            if (! $this->validate($rules)) {
                $error = implode(' ', $this->validator->getErrors());

                return $isAjax ? $this->ajaxError($error) : redirect()->to('/my-tasks')->with('flash', ['type' => 'danger', 'msg' => $error]);
            }

            try {
                $existing  = $submissionModel->findForTaskAndUser($taskId, (int) $user['id']);
                $targetDir = WRITEPATH . 'uploads/tasks/' . $taskId;

                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $newName = $file->getRandomName();
                $file->move($targetDir, $newName);

                if ($existing && is_file($existing['file_path'])) {
                    unlink($existing['file_path']);
                }

                $data = [
                    'task_id'      => $taskId,
                    'user_id'      => $user['id'],
                    'file_path'    => $targetDir . DIRECTORY_SEPARATOR . $newName,
                    'file_name'    => $file->getClientName(),
                    'notes'        => $this->request->getPost('notes') ?? '',
                    'status'       => 'Submitted',
                    'submitted_at' => date('Y-m-d H:i:s'),
                ];

                if ($existing) {
                    $submissionModel->update($existing['id'], $data);
                } else {
                    $submissionModel->insert($data);
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

        $tasks = $taskModel->where('assigned_role', $user['role'])->orderBy('deadline', 'ASC')->findAll();

        foreach ($tasks as &$task) {
            $submission          = $submissionModel->findForTaskAndUser($task['id'], (int) $user['id']);
            $task['submission']  = $submission;
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
