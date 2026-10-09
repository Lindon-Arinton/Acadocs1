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
 * Document Management, organized like a file explorer: task folders (one
 * per task, auto-created on the first upload, where admins review each
 * person's upload) can be grouped into plain folders such as "School Forms",
 * and folders can be nested and moved around.
 */
class Documents extends BaseController
{
    // Uploads start as Pending; these are the decisions an admin can make.
    private const REVIEW_STATUSES = ['Reviewed', 'Returned'];

    private const FOLDER_ACTIONS = ['create_folder', 'rename_folder', 'move_folder', 'delete_folder'];

    public function index()
    {
        $folderId = (int) ($this->request->getGet('folder') ?? 0);

        if ($this->request->getMethod() === 'POST') {
            return in_array($this->request->getPost('action'), self::FOLDER_ACTIONS, true)
                ? $this->manageFolders()
                : $this->review($folderId);
        }

        $tree   = (new DocumentFolderModel())->tree();
        $folder = $folderId ? ($tree[$folderId] ?? null) : null;

        if ($folderId && ! $folder) {
            return redirect()->to('/documents');
        }
        if ($folder && $folder['is_task']) {
            return $this->folder($folderId, $tree);
        }

        $search  = trim($this->request->getGet('q') ?? '');
        $folders = DocumentFolderModel::childrenOf($tree, $folder ? $folderId : null);

        // A search looks through every level, not just the current folder.
        if ($search !== '') {
            $folders = array_values(array_filter($tree, static fn ($f) => mb_stripos($f['name'], $search) !== false));
            usort($folders, static fn ($a, $b) => strcasecmp($a['name'], $b['name']));
            foreach ($folders as &$match) {
                $match['path'] = implode(' / ', array_column(array_slice(DocumentFolderModel::pathTo($tree, $match['id']), 0, -1), 'name'));
            }
            unset($match);
        }

        // Task folders are shown Templates-style: a collapsible section listing
        // every uploaded file, so the files are visible without opening each one.
        $taskIds      = array_values(array_filter(array_column($folders, 'task_id')));
        $filesByTask  = [];
        if ($taskIds) {
            $fileRows = (new TaskSubmissionFileModel())
                ->select('task_submission_files.id, task_submission_files.file_name, task_submissions.task_id, task_submissions.status, task_submissions.submitted_at, users.name AS submitter_name')
                ->join('task_submissions', 'task_submissions.id = task_submission_files.task_submission_id')
                ->join('users', 'users.id = task_submissions.user_id')
                ->whereIn('task_submissions.task_id', $taskIds)
                ->orderBy('task_submissions.submitted_at', 'DESC')
                ->orderBy('task_submission_files.id', 'ASC')
                ->findAll();
            foreach ($fileRows as $row) {
                $row['ext'] = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
                $filesByTask[(int) $row['task_id']][] = $row;
            }
        }
        $fileTypes = array_values(array_unique(array_merge([], ...array_map(
            static fn ($files) => array_column($files, 'ext'),
            array_values($filesByTask)
        ))));
        sort($fileTypes);

        return view('pages/admin/documents', [
            'pageTitle'  => $folder ? $folder['name'] : 'Manage Documents',
            'current'    => $folder,
            'path'       => $folder ? DocumentFolderModel::pathTo($tree, $folderId) : [],
            'folders'    => $folders,
            'filesByTask' => $filesByTask,
            'fileTypes'  => array_values(array_filter($fileTypes)),
            'search'     => $search,
            'canManage'  => hasRole('admin', 'adas'),
            // Every folder, for the "Move to…" picker.
            'allFolders' => array_values(array_map(static fn ($f) => [
                'id'     => $f['id'],
                'parent' => $f['parent_id'],
                'name'   => $f['name'],
                'task'   => $f['is_task'],
            ], $tree)),
        ]);
    }

    /**
     * Create / rename / move / delete folders. Moves are validated so a
     * folder can only go into a plain folder (or the top level), never into
     * itself or one of its own subfolders.
     */
    private function manageFolders()
    {
        $isAjax = $this->request->isAJAX();
        $fail   = fn (string $msg, int $code = 400) => $isAjax ? $this->ajaxError($msg, $code) : redirect()->to('/documents');

        if (! hasRole('admin', 'adas')) {
            return $fail('You are not authorized to do this.', 403);
        }

        $model  = new DocumentFolderModel();
        $tree   = $model->tree();
        $action = $this->request->getPost('action');
        $name   = trim((string) $this->request->getPost('name'));

        // A sibling with the same name would make the two impossible to tell apart.
        $nameTaken = static function (?int $parentId, string $name, ?int $exceptId = null) use ($tree): bool {
            foreach ($tree as $f) {
                if ($f['parent_id'] === $parentId && $f['id'] !== $exceptId && mb_strtolower($f['name']) === mb_strtolower($name)) {
                    return true;
                }
            }

            return false;
        };

        try {
            switch ($action) {
                case 'create_folder':
                    $parentId = (int) $this->request->getPost('parent_id') ?: null;
                    if ($parentId !== null && (! isset($tree[$parentId]) || $tree[$parentId]['is_task'])) {
                        return $fail('Folders can only be created inside a plain folder.');
                    }
                    if ($name === '' || mb_strlen($name) > 200) {
                        return $fail('Please enter a folder name (up to 200 characters).');
                    }
                    if ($nameTaken($parentId, $name)) {
                        return $fail('A folder named "' . $name . '" already exists here.');
                    }
                    $model->insert(['name' => $name, 'parent_id' => $parentId, 'task_id' => null]);
                    $message = 'Folder "' . $name . '" created.';
                    break;

                case 'rename_folder':
                    $folder = $tree[(int) $this->request->getPost('id')] ?? null;
                    if (! $folder) {
                        return $fail('Folder not found.', 404);
                    }
                    if ($name === '' || mb_strlen($name) > 200) {
                        return $fail('Please enter a folder name (up to 200 characters).');
                    }
                    if ($nameTaken($folder['parent_id'], $name, $folder['id'])) {
                        return $fail('A folder named "' . $name . '" already exists here.');
                    }
                    $model->update($folder['id'], ['name' => $name]);
                    $message = 'Folder renamed to "' . $name . '".';
                    break;

                case 'move_folder':
                    $folder   = $tree[(int) $this->request->getPost('id')] ?? null;
                    $targetId = (int) $this->request->getPost('target_id') ?: null;
                    if (! $folder) {
                        return $fail('Folder not found.', 404);
                    }
                    if ($targetId !== null && ! isset($tree[$targetId])) {
                        return $fail('The destination folder no longer exists.');
                    }
                    if ($targetId !== null && $tree[$targetId]['is_task']) {
                        return $fail('Task folders hold uploads, not other folders. Pick a plain folder.');
                    }
                    if ($targetId === $folder['id'] || in_array($targetId, DocumentFolderModel::descendantIds($tree, $folder['id']), true)) {
                        return $fail("A folder can't be moved into itself or one of its own subfolders.");
                    }
                    if ($targetId === $folder['parent_id']) {
                        return $fail('"' . $folder['name'] . '" is already in that folder.');
                    }
                    if ($nameTaken($targetId, $folder['name'], $folder['id'])) {
                        return $fail('The destination already has a folder named "' . $folder['name'] . '". Rename one of them first.');
                    }
                    $model->update($folder['id'], ['parent_id' => $targetId]);
                    $message = '"' . $folder['name'] . '" moved to ' . ($targetId ? '"' . $tree[$targetId]['name'] . '"' : 'the top level') . '.';
                    break;

                case 'delete_folder':
                    $folder = $tree[(int) $this->request->getPost('id')] ?? null;
                    if (! $folder) {
                        return $fail('Folder not found.', 404);
                    }
                    if ($folder['is_task']) {
                        return $fail('Task folders are removed together with their task.');
                    }
                    // Its contents move up one level instead of being deleted.
                    foreach (DocumentFolderModel::childrenOf($tree, $folder['id']) as $child) {
                        if ($nameTaken($folder['parent_id'], $child['name'], $child['id'])) {
                            return $fail("Can't delete: \"" . $child['name'] . '" would clash with a folder of the same name one level up. Rename it first.');
                        }
                    }
                    $model->where('parent_id', $folder['id'])->set(['parent_id' => $folder['parent_id']])->update();
                    $model->delete($folder['id']);
                    $message = 'Folder "' . $folder['name'] . '" deleted. Its contents were moved up one level.';
                    break;

                default:
                    return $fail('Unknown action.');
            }
        } catch (\Throwable $e) {
            return $fail('Something went wrong: ' . $e->getMessage());
        }

        return $isAjax ? $this->ajaxSuccess($message) : redirect()->to('/documents');
    }

    /**
     * A task folder: every submitter's upload for that task.
     */
    private function folder(int $folderId, array $tree)
    {
        $folder = (new DocumentFolderModel())->find($folderId);
        $task   = $folder ? (new TaskModel())->find($folder['task_id']) : null;

        if (! $folder || ! $task) {
            return redirect()->to('/documents');
        }

        $submissions = (new TaskSubmissionModel())->forTask((int) $task['id']);

        $submissionIds = array_column($submissions, 'id');
        $filesGrouped  = (new TaskSubmissionFileModel())->forSubmissions($submissionIds);

        $feedbackGrouped = (new TaskFeedbackModel())->forSubmissions($submissionIds);

        foreach ($submissions as &$submission) {
            $submission['files']    = $filesGrouped[$submission['id']] ?? [];
            $submission['feedback'] = $feedbackGrouped[$submission['id']] ?? [];
        }
        unset($submission);

        return view('pages/admin/document_folder', [
            'pageTitle'   => $folder['name'],
            'folder'      => $folder,
            'path'        => DocumentFolderModel::pathTo($tree, $folderId),
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

        // Approving teachers' work is the principal's call; ADAS can view
        // submissions but not mark them Reviewed/Returned.
        if (! hasRole('admin')) {
            return $isAjax ? $this->ajaxError('Only the principal can review submissions.', 403) : redirect()->to($backUrl);
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

        // Re-saving the same decision would change nothing (and look like a no-op).
        if ($submission['status'] === $status) {
            $error = 'This upload is already marked ' . $status . '.';

            return $isAjax ? $this->ajaxError($error) : redirect()->to($backUrl)->with('flash', ['type' => 'warning', 'msg' => $error]);
        }

        if ($status === 'Returned' && $comment === '') {
            $error = 'Please say what needs to be fixed before returning it.';

            return $isAjax ? $this->ajaxError($error) : redirect()->to($backUrl)->with('flash', ['type' => 'danger', 'msg' => $error]);
        }

        try {
            $submissionModel->update($submissionId, [
                'status'      => $status,
                'reviewed_by' => currentUser()['id'],
                'reviewed_at' => date('Y-m-d H:i:s'),
            ]);

            if ($comment !== '') {
                (new TaskFeedbackModel())->insert([
                    'task_submission_id' => $submissionId,
                    'comment'            => $comment,
                    'author_id'          => currentUser()['id'],
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
