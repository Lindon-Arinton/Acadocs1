<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\OfficeConverter;
use App\Models\DocumentModel;
use App\Models\TeacherModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class DocumentFile extends BaseController
{
    private const OFFICE_TO_PDF = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    public function show(int $id)
    {
        $fullPath = $this->authorizedPath($id);
        $ext      = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        $inlineMimes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'txt'  => 'text/plain',
        ];

        if (isset($inlineMimes[$ext])) {
            return $this->response
                ->setHeader('Content-Type', $inlineMimes[$ext])
                ->setHeader('Content-Disposition', 'inline; filename="' . basename($fullPath) . '"')
                ->setBody(file_get_contents($fullPath));
        }

        if (in_array($ext, self::OFFICE_TO_PDF, true)) {
            $pdfPath = $this->convertedPdf($id, $fullPath);

            if ($pdfPath) {
                return $this->response
                    ->setHeader('Content-Type', 'application/pdf')
                    ->setHeader('Content-Disposition', 'inline; filename="' . pathinfo($fullPath, PATHINFO_FILENAME) . '.pdf"')
                    ->setBody(file_get_contents($pdfPath));
            }
        }

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody('<div style="font-family:sans-serif;color:#6b7280;text-align:center;padding:3rem 1rem;">'
                . 'Preview isn\'t available for this file type. Download it to view the contents.</div>');
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

    private function convertedPdf(int $docId, string $sourcePath): ?string
    {
        $cacheDir = WRITEPATH . 'cache/document_files';

        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $cached = $cacheDir . DIRECTORY_SEPARATOR . $docId . '.pdf';

        if (is_file($cached) && filemtime($cached) >= filemtime($sourcePath)) {
            return $cached;
        }

        $result = (new OfficeConverter())->convert($sourcePath, 'pdf', $cacheDir);

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
