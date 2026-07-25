<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use App\Models\TaskAssigneeModel;
use App\Models\TaskFeedbackModel;
use App\Models\TaskModel;
use App\Models\TaskSubmissionFileModel;
use App\Models\TaskSubmissionModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Tasks extends BaseController
{
    public function index()
    {
        if (! hasRole('admin', 'adas')) {
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
                    $deadlineDate = $this->request->getPost('deadline_date');
                    $deadlineTime = $this->request->getPost('deadline_time') ?: '00:00';

                    if ($deadlineDate < date('Y-m-d')) {
                        $error = 'Deadline cannot be in the past.';

                        return $isAjax ? $this->ajaxError($error) : redirect()->to('/tasks')->with('flash', ['type' => 'danger', 'msg' => $error]);
                    }

                    $specificUserIds = [];
                    if ($assignedRole === 'specific') {
                        $specificUserIds = array_unique(array_filter(array_map(
                            'intval',
                            is_array($this->request->getPost('user_ids')) ? $this->request->getPost('user_ids') : []
                        )));

                        if (empty($specificUserIds)) {
                            $error = 'Please choose at least one person.';

                            return $isAjax ? $this->ajaxError($error) : redirect()->to('/tasks')->with('flash', ['type' => 'danger', 'msg' => $error]);
                        }
                    }

                    $deadline = $deadlineDate . ' ' . $deadlineTime . ':00';

                    $taskId = $taskModel->insert([
                        'title'         => $title,
                        'description'   => $this->request->getPost('description') ?? '',
                        'assigned_role' => $assignedRole,
                        'deadline'      => $deadline,
                        'created_by'    => currentUser()['name'],
                    ]);

                    $recipients = $assignedRole === 'specific'
                        ? (new UserModel())->whereIn('id', $specificUserIds)->findAll()
                        : (new UserModel())->where('role', $assignedRole)->findAll();

                    if ($assignedRole === 'specific') {
                        $assigneeModel = new TaskAssigneeModel();
                        foreach ($specificUserIds as $uid) {
                            $assigneeModel->insert(['task_id' => $taskId, 'user_id' => $uid]);
                        }
                    }

                    $notifModel = new NotificationModel();
                    foreach ($recipients as $recipient) {
                        $notifModel->insert([
                            'user_id'  => $recipient['id'],
                            'type'     => 'task_assigned',
                            'title'    => 'New Task: ' . $title,
                            'sub'      => 'Due ' . date('M d, Y h:i A', strtotime($deadline)),
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
        $assigneeModel   = new TaskAssigneeModel();

        $tasks = $taskModel->orderBy('deadline', 'ASC')->findAll();
        foreach ($tasks as &$task) {
            $task['eligible_count']  = $task['assigned_role'] === 'specific'
                ? $assigneeModel->where('task_id', $task['id'])->countAllResults()
                : $userModel->where('role', $task['assigned_role'])->countAllResults();
            $task['submitted_count'] = $submissionModel->where('task_id', $task['id'])->countAllResults();
        }
        unset($task);

        $assignableUsers = $userModel->whereIn('role', ['teacher', 'adas'])->orderBy('name', 'ASC')->findAll();

        return view('pages/admin/tasks', [
            'pageTitle'       => 'Tasks & Assignments',
            'tasks'           => $tasks,
            'assignableUsers' => $assignableUsers,
            'flash'           => session()->getFlashdata('flash'),
        ]);
    }

    public function view(int $id)
    {
        if (! hasRole('admin', 'adas')) {
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

        $fileModel   = new TaskSubmissionFileModel();
        $submissions = $submissionModel->forTask($id);
        foreach ($submissions as &$submission) {
            $submission['feedback'] = $feedbackModel->where('task_submission_id', $submission['id'])
                ->orderBy('date', 'DESC')->findAll();
            $submission['files'] = $fileModel->forSubmission($submission['id']);
        }
        unset($submission);

        $submittedUserIds = array_column($submissions, 'user_id');
        $pendingUsers      = $task['assigned_role'] === 'specific'
            ? (new TaskAssigneeModel())->usersForTask($id)
            : (new UserModel())->where('role', $task['assigned_role'])->orderBy('name')->findAll();
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
