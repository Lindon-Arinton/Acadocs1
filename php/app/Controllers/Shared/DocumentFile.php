<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\FilePreview;
use App\Models\DocumentModel;
use App\Models\TeacherModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class DocumentFile extends BaseController
{
    public function show(int $id)
    {
        $fullPath = $this->authorizedPath($id);

        // Separate cache dir from DocumentFileDownload's: document ids and
        // document_file ids overlap, so sharing one would mix up cached previews.
        return (new FilePreview(WRITEPATH . 'cache/documents'))
            ->respond($this->response, $fullPath, basename($fullPath), $id);
    }

    public function download(int $id)
    {
        $fullPath = $this->authorizedPath($id);

        return $this->response->download($fullPath, null)->setFileName(basename($fullPath));
    }

    /**
     * Resolves the document's file path, checking the caller is either
     * staff or the teacher who owns the document.
     */
    private function authorizedPath(int $id): string
    {
        $doc = (new DocumentModel())->find($id);

        if (! $doc || ! $doc['file_path']) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! hasRole('admin', 'adas')) {
            $teacher = (new TeacherModel())->resolveForUser(currentUser());

            if (! $teacher || (int) $doc['teacher_id'] !== (int) $teacher['id']) {
                throw PageNotFoundException::forPageNotFound();
            }
        }

        $fullPath = ROOTPATH . $doc['file_path'];

        if (! is_file($fullPath)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $fullPath;
    }
}
