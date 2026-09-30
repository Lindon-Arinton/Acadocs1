<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\FilePreview;
use App\Models\TaskSubmissionFileModel;
use App\Models\TaskSubmissionModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class TaskDownload extends BaseController
{
    public function show(int $fileId)
    {
        $file = $this->authorizedFile($fileId);

        if (! is_file($file['file_path'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($file['file_path'], null)->setFileName($file['file_name']);
    }

    public function preview(int $fileId)
    {
        $file = $this->authorizedFile($fileId);

        if (! is_file($file['file_path'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return (new FilePreview(WRITEPATH . 'cache/task_files'))
            ->respond($this->response, $file['file_path'], $file['file_name'], $file['id']);
    }

    /**
     * Loads the file and verifies the current user is either the submitter or
     * someone who reviews task submissions (admin/ADAS).
     */
    private function authorizedFile(int $fileId): array
    {
        $file = (new TaskSubmissionFileModel())->find($fileId);

        if (! $file) {
            throw PageNotFoundException::forPageNotFound();
        }

        $submission = (new TaskSubmissionModel())->find($file['task_submission_id']);
        $user       = currentUser();

        if (! $submission || (! hasRole('admin', 'adas') && (int) $submission['user_id'] !== (int) $user['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $file;
    }
}
