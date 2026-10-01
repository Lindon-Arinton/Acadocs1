<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\OfficeConverter;
use App\Models\NotificationModel;
use App\Models\TaskModel;
use App\Models\TaskSubmissionFileModel;
use App\Models\TaskSubmissionModel;
use App\Models\UserModel;
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
     * In-app annotator (pen / highlighter / text) for a submitted file — the
     * browser's own PDF viewer can draw, but its marks never reach the
     * server. Works on PDFs, images, and Office files the server can convert
     * to PDF. If the file was already annotated, that copy is reopened so new
     * marks add to the old ones.
     */
    public function annotate(int $fileId)
    {
        if (! hasRole('admin', 'adas')) {
            throw PageNotFoundException::forPageNotFound();
        }

        $file  = $this->authorizedFile($fileId);
        $ext   = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
        $fresh = (bool) $this->request->getGet('fresh'); // ignore the saved annotated copy, start from the original
        $reuse = ! $fresh && TaskSubmissionFileModel::hasAnnotation($file);

        $kind = match (true) {
            $reuse, $ext === 'pdf' => 'pdf',
            in_array($ext, ['jpg', 'jpeg', 'png'], true)                  => 'image',
            in_array($ext, self::OFFICE_TO_PDF, true)                     => $this->convertedPdf($file) ? 'pdf' : null,
            default                                                        => null,
        };

        $submission = (new TaskSubmissionModel())->find($file['task_submission_id']);
        $task       = $submission ? (new TaskModel())->find($submission['task_id']) : null;
        $submitter  = $submission ? (new UserModel())->find($submission['user_id']) : null;

        return view('pages/admin/annotate', [
            'file'          => $file,
            'kind'          => $kind,
            'imageType'     => $ext === 'png' ? 'png' : 'jpg',
            'fresh'         => $fresh,
            'taskTitle'     => $task['title'] ?? 'Task',
            'submitterName' => $submitter['name'] ?? '',
            'hasAnnotation' => TaskSubmissionFileModel::hasAnnotation($file),
        ]);
    }

    /** The bytes the annotator draws on: the annotated copy if any, else the original (as PDF where possible). */
    public function annotationSource(int $fileId)
    {
        if (! hasRole('admin', 'adas')) {
            throw PageNotFoundException::forPageNotFound();
        }

        $file = $this->authorizedFile($fileId);
        $ext  = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));

        $path = ! $this->request->getGet('original') && TaskSubmissionFileModel::hasAnnotation($file)
            ? TaskSubmissionFileModel::annotatedPath($file)
            : null;
        $mime = 'application/pdf';

        if ($path === null) {
            if ($ext === 'pdf' || in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $path = $file['file_path'];
                $mime = ['pdf' => 'application/pdf', 'png' => 'image/png'][$ext] ?? 'image/jpeg';
            } elseif (in_array($ext, self::OFFICE_TO_PDF, true)) {
                $path = $this->convertedPdf($file);
            }
        }

        if (! $path || ! is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control', 'no-store')
            ->setBody(file_get_contents($path));
    }

    /** Stores the annotator's flattened PDF and lets the submitter know. */
    public function saveAnnotation(int $fileId)
    {
        if (! hasRole('admin', 'adas')) {
            return $this->ajaxError('You are not authorized to do this.', 403);
        }

        $file   = $this->authorizedFile($fileId);
        $upload = $this->request->getFile('annotated');

        if (! $upload || ! $upload->isValid()) {
            return $this->ajaxError('Nothing was uploaded.');
        }
        if ($upload->getSize() > 50 * 1024 * 1024) {
            return $this->ajaxError('The annotated file is too large (50 MB max).');
        }
        if (file_get_contents($upload->getTempName(), false, null, 0, 5) !== '%PDF-') {
            return $this->ajaxError('The annotated file is not a valid PDF.');
        }

        $target = TaskSubmissionFileModel::annotatedPath($file);
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }
        if (! copy($upload->getTempName(), $target)) {
            return $this->ajaxError('Could not save the annotated file.');
        }

        $submission = (new TaskSubmissionModel())->find($file['task_submission_id']);
        $task       = (new TaskModel())->find($submission['task_id']);
        $submitter  = (new UserModel())->find((int) $submission['user_id']);

        if ($submitter && (int) $submitter['id'] !== (int) currentUser()['id']) {
            (new NotificationModel())->upsertGrouped(
                (int) $submitter['id'],
                'task_feedback',
                (int) $submission['id'],
                'task_feedback',
                'Your file was marked up: ' . ($task['title'] ?? 'Task'),
                currentUser()['name'] . ' added notes on ' . $file['file_name'],
                base_url($submitter['role'] === 'teacher' ? 'submit-documents' : 'my-tasks')
            );
        }

        return $this->ajaxSuccess('Annotations saved. ' . ($submitter['name'] ?? 'The submitter') . ' can now see the marked-up copy.');
    }

    /** The marked-up copy: inline by default, ?download=1 to save it. */
    public function annotated(int $fileId)
    {
        $file = $this->authorizedFile($fileId);
        $path = TaskSubmissionFileModel::annotatedPath($file);

        if (! is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $name = pathinfo($file['file_name'], PATHINFO_FILENAME) . ' (annotated).pdf';

        if ($this->request->getGet('download')) {
            return $this->response->download($path, null)->setFileName($name);
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $name . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody(file_get_contents($path));
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
