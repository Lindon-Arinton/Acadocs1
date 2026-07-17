<?php

namespace App\Controllers;

use App\Models\TaskSubmissionModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class TaskDownload extends BaseController
{
    public function show(int $id)
    {
        $submission = (new TaskSubmissionModel())->find($id);

        if (! $submission) {
            throw PageNotFoundException::forPageNotFound();
        }

        $user = currentUser();

        if (! hasRole('admin') && (int) $submission['user_id'] !== (int) $user['id']) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! is_file($submission['file_path'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response->download($submission['file_path'], null)->setFileName($submission['file_name']);
    }
}
