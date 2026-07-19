<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use App\Models\TaskFeedbackModel;
use App\Models\TaskModel;
use App\Models\TaskSubmissionModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Tasks extends BaseController
{
    public function index()
    {
        if (! hasRole('admin')) {
            return redirect()->to('/dashboard');
        }

        $taskModel = new TaskModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax  = $this->request->isAJAX();
            $action  = $this->request->getPost('action');
            $message = null;

            try {
                if ($action === 'add') {
                    $title        = $this->request->getPost('title');
                    $assignedRole = $this->request->getPost('assigned_role');
                    $deadline     = $this->request->getPost('deadline');

                    $taskId = $taskModel->insert([
                        'title'         => $title,
                        'description'   => $this->request->getPost('description') ?? '',
                        'assigned_role' => $assignedRole,
                        'deadline'      => $deadline,
                        'created_by'    => currentUser()['name'],
                    ]);

                    $notifModel = new NotificationModel();
                    foreach ((new UserModel())->where('role', $assignedRole)->findAll() as $recipient) {
                        $notifModel->insert([
                            'user_id'  => $recipient['id'],
                            'type'     => 'task_assigned',
                            'title'    => 'New Task: ' . $title,
                            'sub'      => 'Due ' . date('M d, Y', strtotime($deadline)),
                            'url'      => base_url('my-tasks'),
                            'ref_type' => 'task_assigned',
                            'ref_id'   => $taskId,
                            'is_read'  => 0,
                        ]);
                    }

                    $message = 'Task posted successfully.';
                } elseif ($action === 'close') {
                    $taskModel->update((int) $this->request->getPost('id'), ['status' => 'Closed']);
                    $message = 'Task closed.';
                } elseif ($action === 'reopen') {
                    $taskModel->update((int) $this->request->getPost('id'), ['status' => 'Open']);
                    $message = 'Task reopened.';
                } elseif ($action === 'delete') {
                    $taskModel->delete((int) $this->request->getPost('id'));
                    $message = 'Task deleted.';
                }
            } catch (\Throwable $e) {
                return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/tasks');
            }

            if ($isAjax) {
                return $message ? $this->ajaxSuccess($message) : $this->ajaxError('Unknown action.');
            }

            session()->setFlashdata('flash', ['type' => 'success', 'msg' => $message ?? '']);

            return redirect()->to('/tasks');
        }

        $userModel       = new UserModel();
        $submissionModel = new TaskSubmissionModel();

        $tasks = $taskModel->orderBy('deadline', 'ASC')->findAll();
        foreach ($tasks as &$task) {
            $task['eligible_count']  = $userModel->where('role', $task['assigned_role'])->countAllResults();
            $task['submitted_count'] = $submissionModel->where('task_id', $task['id'])->countAllResults();
        }
        unset($task);

        return view('pages/admin/tasks', [
            'pageTitle' => 'Tasks & Assignments',
            'tasks'     => $tasks,
            'flash'     => session()->getFlashdata('flash'),
        ]);
    }

    public function view(int $id)
    {
        if (! hasRole('admin')) {
            return redirect()->to('/dashboard');
        }

        $taskModel = new TaskModel();
        $task      = $taskModel->find($id);

        if (! $task) {
            throw PageNotFoundException::forPageNotFound();
        }

        $submissionModel = new TaskSubmissionModel();
        $feedbackModel   = new TaskFeedbackModel();

        if ($this->request->getMethod() === 'POST') {
            $isAjax       = $this->request->isAJAX();
            $submissionId = (int) $this->request->getPost('submission_id');
            $comment      = trim($this->request->getPost('comment') ?? '');

            if ($submissionId && $comment) {
                try {
                    $feedbackModel->insert([
                        'task_submission_id' => $submissionId,
                        'comment'             => $comment,
                        'date'                => date('Y-m-d'),
                    ]);
                    $submissionModel->update($submissionId, ['status' => 'Reviewed']);

                    $submission = $submissionModel->find($submissionId);
                    if ($submission) {
                        (new NotificationModel())->upsertGrouped(
                            (int) $submission['user_id'],
                            'task_feedback',
                            $submissionId,
                            'task_feedback',
                            'New feedback on: ' . $task['title'],
                            mb_strimwidth($comment, 0, 80, '…'),
                            base_url('my-tasks')
                        );
                    }
                } catch (\Throwable $e) {
                    return $isAjax ? $this->ajaxError('Something went wrong: ' . $e->getMessage()) : redirect()->to('/tasks/' . $id);
                }

                if ($isAjax) {
                    return $this->ajaxSuccess('Feedback sent.');
                }

                session()->setFlashdata('flash', ['type' => 'success', 'msg' => 'Feedback sent.']);

                return redirect()->to('/tasks/' . $id);
            }

            if ($isAjax) {
                return $this->ajaxError('Please enter a comment before submitting.');
            }
        }

        $submissions = $submissionModel->forTask($id);
        foreach ($submissions as &$submission) {
            $submission['feedback'] = $feedbackModel->where('task_submission_id', $submission['id'])
                ->orderBy('date', 'DESC')->findAll();
        }
        unset($submission);

        $submittedUserIds = array_column($submissions, 'user_id');
        $pendingUsers      = (new UserModel())->where('role', $task['assigned_role'])
            ->orderBy('name')->findAll();
        $pendingUsers      = array_values(array_filter(
            $pendingUsers,
            static fn (array $u) => ! in_array($u['id'], $submittedUserIds, true)
        ));

        return view('pages/admin/task_detail', [
            'pageTitle'    => 'Task: ' . $task['title'],
            'task'         => $task,
            'submissions'  => $submissions,
            'pendingUsers' => $pendingUsers,
            'flash'        => session()->getFlashdata('flash'),
        ]);
    }
}
