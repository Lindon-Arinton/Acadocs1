<?php

namespace App\Controllers\Shared;

use App\Controllers\BaseController;
use App\Libraries\OfficeOnlinePreview;
use App\Models\DocumentFileModel;
use App\Models\TemplateModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Serves a file to Microsoft's Office Online viewer through a signed,
 * expiring link (OfficeOnlinePreview::signedUrl). This route is outside
 * authGuard on purpose — the viewer has no session — so the HMAC signature
 * and expiry are the only access check. Links are only ever issued to users
 * who were already allowed to preview the file.
 */
class PublicFile extends BaseController
{
    private const MIME = [
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    ];

    public function show(string $kind, int $id, int $expires, string $signature)
    {
        if (! OfficeOnlinePreview::verify($kind, $id, $expires, $signature)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $file = match ($kind) {
            'template' => (new TemplateModel())->find($id),
            'docfile'  => (new DocumentFileModel())->find($id),
            default    => null,
        };

        if (! $file || ! is_file($file['file_path'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        $ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
        if (! isset(self::MIME[$ext])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', self::MIME[$ext])
            ->setHeader('Content-Disposition', 'inline; filename="' . str_replace('"', '', $file['file_name']) . '"')
            ->setHeader('Cache-Control', 'private, max-age=0, no-store')
            ->setHeader('X-Robots-Tag', 'noindex')
            ->setBody(file_get_contents($file['file_path']));
    }
}
