<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\DocumentFolderModel;
use App\Models\NotificationModel;
use App\Models\TaskAssigneeModel;
use App\Models\TaskFeedbackModel;
use App\Models\TaskModel;
use App\Models\TaskSubmissionFileModel;
use App\Models\TaskSubmissionModel;
use App\Models\TeacherSubjectModel;
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
                    $deadlineTime = $this->request->getPost('deadline_time') ?: '23:59';

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
                            // Teachers now manage tasks on the merged To Do
                            // List (/submit-documents); ADAS still has its
                            // own separate My Tasks page.
                            'url'      => base_url($recipient['role'] === 'teacher' ? 'submit-documents' : 'my-tasks'),
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
                    $taskId = (int) $this->request->getPost('id');
                    (new NotificationModel())->deleteForTask($taskId); // before the delete cascades away its submissions
                    $taskModel->delete($taskId);
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

        $assignableUsers     = $userModel->whereIn('role', ['teacher', 'adas'])->orderBy('name', 'ASC')->findAll();
        $departmentsByUserId = (new TeacherSubjectModel())->departmentsByUserId();
        foreach ($assignableUsers as &$u) {
            $u['departments'] = $departmentsByUserId[(int) $u['id']] ?? [];
        }
        unset($u);
        $departments = array_values(array_unique(array_merge([], ...array_values($departmentsByUserId))));
        sort($departments);

        return view('pages/admin/tasks', [
            'pageTitle'       => 'Tasks & Assignments',
            'tasks'           => $tasks,
            'assignableUsers' => $assignableUsers,
            'departments'     => $departments,
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
            $isAjax = $this->request->isAJAX();

            // Feedback marks the submission Reviewed — the principal's call only.
            if (! hasRole('admin')) {
                return $isAjax ? $this->ajaxError('Only the principal can review submissions.', 403) : redirect()->to('/tasks/' . $id);
            }

            $submissionId = (int) $this->request->getPost('submission_id');
            $comment      = trim($this->request->getPost('comment') ?? '');

            if ($submissionId && $comment) {
                try {
                    $feedbackModel->insert([
                        'task_submission_id' => $submissionId,
                        'comment'             => $comment,
                        'author_id'           => currentUser()['id'],
                        'date'                => date('Y-m-d'),
                    ]);
                    $submissionModel->update($submissionId, [
                        'status'      => 'Reviewed',
                        'reviewed_by' => currentUser()['id'],
                        'reviewed_at' => date('Y-m-d H:i:s'),
                    ]);

                    $submission = $submissionModel->find($submissionId);
                    if ($submission) {
                        $submitter = (new UserModel())->find((int) $submission['user_id']);
                        (new NotificationModel())->upsertGrouped(
                            (int) $submission['user_id'],
                            'task_feedback',
                            $submissionId,
                            'task_feedback',
                            'New feedback on: ' . $task['title'],
                            mb_strimwidth($comment, 0, 80, '…'),
                            base_url(($submitter['role'] ?? null) === 'teacher' ? 'submit-documents' : 'my-tasks')
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
            $submission['feedback'] = $feedbackModel->forSubmission((int) $submission['id']);
            $submission['files']    = $fileModel->forSubmission($submission['id']);
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
            // The task's Manage Documents folder (created on its first upload), for the shortcut.
            'folderId'     => (int) ((new DocumentFolderModel())->where('task_id', $id)->first()['id'] ?? 0) ?: null,
            'flash'        => session()->getFlashdata('flash'),
        ]);
    }

    /**
     * JSON version of view()'s data, fed to the "expand a row" modal on the
     * tasks list so a submission can be inspected without leaving the list
     * (the tasks/{id} page itself stays as-is for notification deep links).
     */
    public function data(int $id)
    {
        if (! hasRole('admin', 'adas')) {
            return $this->ajaxError('You are not authorized to do this.', 403);
        }

        $taskModel = new TaskModel();
        $task      = $taskModel->find($id);

        if (! $task) {
            return $this->ajaxError('Task not found.', 404);
        }

        $submissionModel = new TaskSubmissionModel();
        $feedbackModel   = new TaskFeedbackModel();
        $fileModel       = new TaskSubmissionFileModel();

        $submissions = $submissionModel->forTask($id);
        foreach ($submissions as &$submission) {
            $submission['feedback'] = $feedbackModel->forSubmission((int) $submission['id']);
            $submission['files']    = $fileModel->forSubmission($submission['id']);
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

        return $this->response->setJSON([
            'status' => 'success',
            'task'   => [
                'id'            => (int) $task['id'],
                'title'         => $task['title'],
                'description'   => $task['description'],
                'assignedRole'  => $task['assigned_role'],
                'status'        => $task['status'],
                'createdAt'     => date('M d, Y', strtotime($task['created_at'])),
                'deadline'      => date('M d, Y h:i A', strtotime($task['deadline'])),
                'assigneeCount' => count($pendingUsers) + count($submissions),
                'folderUrl'     => ($folder = (new DocumentFolderModel())->where('task_id', $id)->first())
                    ? base_url('documents?folder=' . (int) $folder['id']) : null,
            ],
            'submissions' => array_map(static fn (array $s) => submissionTiming($s['submitted_at'], $task['deadline']) + [
                'id'            => (int) $s['id'],
                'userId'        => (int) $s['user_id'],
                'submitterName' => $s['submitter_name'],
                'status'        => $s['status'],
                'reviewerName'  => $s['reviewer_name'] ?? null,
                'reviewedAt'    => $s['reviewed_at'] ? date('M d, Y h:i A', strtotime($s['reviewed_at'])) : null,
                'notes'         => $s['notes'],
                'submittedAt'   => date('M d, Y h:i A', strtotime($s['submitted_at'])),
                'files'         => array_map(static fn (array $f) => [
                    'id'   => (int) $f['id'],
                    'name' => $f['file_name'],
                    'ext'  => strtolower(pathinfo($f['file_name'], PATHINFO_EXTENSION)),
                    'annotated' => TaskSubmissionFileModel::hasAnnotation($f),
                ], $s['files']),
                'feedback' => array_map(static fn (array $fb) => [
                    'comment' => $fb['comment'],
                    'author'  => $fb['author_name'] ?? null,
                    'date'    => date('M d, Y', strtotime($fb['date'])),
                ], $s['feedback']),
            ], $submissions),
            'canReview'    => hasRole('admin'),
            'pendingUsers' => array_map(static fn (array $u) => ['id' => (int) $u['id'], 'name' => $u['name']], $pendingUsers),
        ]);
    }
}
