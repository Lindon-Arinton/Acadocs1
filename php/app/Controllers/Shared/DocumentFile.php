<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\TeacherModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Mimes;

class DocumentFile extends BaseController
{
    public function show(int $id)
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

        $ext  = pathinfo($fullPath, PATHINFO_EXTENSION);
        $mime = Mimes::guessTypeFromExtension($ext) ?? 'application/octet-stream';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($fullPath) . '"')
            ->setBody(file_get_contents($fullPath));
    }
}
