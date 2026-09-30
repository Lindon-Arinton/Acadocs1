<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\FilePreview;
use App\Models\DocumentFileModel;
use App\Models\DocumentModel;
use App\Models\TeacherModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class DocumentFileDownload extends BaseController
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

        return (new FilePreview(WRITEPATH . 'cache/document_files'))
            ->respond($this->response, $file['file_path'], $file['file_name'], $file['id']);
    }

    /**
     * Loads the file and verifies the current user is either staff or the
     * teacher who owns the parent document.
     */
    private function authorizedFile(int $fileId): array
    {
        $file = (new DocumentFileModel())->find($fileId);

        if (! $file) {
            throw PageNotFoundException::forPageNotFound();
        }

        $doc = (new DocumentModel())->find($file['document_id']);

        if (! $doc) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! hasRole('admin', 'adas')) {
            $teacher = (new TeacherModel())->resolveForUser(currentUser());

            if (! $teacher || (int) $doc['teacher_id'] !== (int) $teacher['id']) {
                throw PageNotFoundException::forPageNotFound();
            }
        }

        return $file;
    }
}
