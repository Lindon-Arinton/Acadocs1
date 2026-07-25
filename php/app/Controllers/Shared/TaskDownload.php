<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\OfficeConverter;
use App\Models\TaskSubmissionFileModel;
use App\Models\TaskSubmissionModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class TaskDownload extends BaseController
{
    private const OFFICE_TO_PDF = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

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

        $ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));

        $mimeMap = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ];

        if (isset($mimeMap[$ext])) {
            return $this->response
                ->setHeader('Content-Type', $mimeMap[$ext])
                ->setHeader('Content-Disposition', 'inline; filename="' . $file['file_name'] . '"')
                ->setBody(file_get_contents($file['file_path']));
        }

        if (in_array($ext, self::OFFICE_TO_PDF, true)) {
            $pdfPath = $this->convertedPdf($file);

            if ($pdfPath) {
                return $this->response
                    ->setHeader('Content-Type', 'application/pdf')
                    ->setHeader('Content-Disposition', 'inline; filename="' . pathinfo($file['file_name'], PATHINFO_FILENAME) . '.pdf"')
                    ->setBody(file_get_contents($pdfPath));
            }
        }

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody('<div style="font-family:sans-serif;color:#6b7280;text-align:center;padding:3rem 1rem;">'
                . 'Preview isn\'t available for this file type. Download it to view the contents.</div>');
    }

    /**
     * Loads the file and verifies the current user is either the submitter or an admin.
     */
    private function authorizedFile(int $fileId): array
    {
        $file = (new TaskSubmissionFileModel())->find($fileId);

        if (! $file) {
            throw PageNotFoundException::forPageNotFound();
        }

        $submission = (new TaskSubmissionModel())->find($file['task_submission_id']);
        $user       = currentUser();

        if (! $submission || (! hasRole('admin') && (int) $submission['user_id'] !== (int) $user['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $file;
    }

    private function convertedPdf(array $file): ?string
    {
        $cacheDir = WRITEPATH . 'cache/task_files';

        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cached = $cacheDir . DIRECTORY_SEPARATOR . $file['id'] . '.pdf';

        if (is_file($cached) && filemtime($cached) >= filemtime($file['file_path'])) {
            return $cached;
        }

        $result = (new OfficeConverter())->convert($file['file_path'], 'pdf', $cacheDir);

        if (! $result) {
            return null;
        }

        if ($result !== $cached) {
            if (is_file($cached)) {
                unlink($cached);
            }
            rename($result, $cached);
        }

        return $cached;
    }
}
