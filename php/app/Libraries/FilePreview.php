<?php

namespace App\Libraries;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Builds the inline preview response for an uploaded file. Needs no external
 * binary (no LibreOffice), so it works the same on shared hosting:
 *
 * - PDF, images, text: streamed inline as-is (PDFs open in the browser's viewer).
 * - .docx .pptx .xlsx .odt .ods .odp .csv: a small page that renders the file
 *   in the browser (public/assets/js/office-viewer.js), with the file embedded.
 * - Legacy binary .doc/.xls (which the browser renderers can't read): rendered
 *   to HTML server-side by PhpWord/PhpSpreadsheet.
 * - Anything else (e.g. .ppt): the "unavailable" response.
 *
 * Every HTML response is sent with `Content-Security-Policy: sandbox`, so the
 * markup generated from an uploaded document runs in an opaque origin - even
 * when the preview URL is opened directly in a tab - and can't reach the
 * app's session or DOM.
 *
 * The unavailable response carries an `X-Preview-Available: 0` header so
 * fetch()-based callers can detect it, and a short message body for callers
 * that point an <iframe> straight at the preview URL.
 */
class FilePreview
{
    private const INLINE_MIMES = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'txt'  => 'text/plain; charset=UTF-8',
    ];

    /** Rendered in the browser by office-viewer.js. */
    private const BROWSER_RENDERED_EXT = ['docx', 'pptx', 'xlsx', 'odt', 'ods', 'odp', 'csv'];

    private const SANDBOX_CSP = 'sandbox allow-scripts allow-popups allow-popups-to-escape-sandbox';

    public function __construct(private string $cacheDir)
    {
    }

    /**
     * @param string     $path     Absolute path of the stored file.
     * @param string     $fileName Original file name (its extension picks the preview type).
     * @param int|string $cacheKey Unique within $cacheDir; names the cached server-side renders.
     */
    public function respond(ResponseInterface $response, string $path, string $fileName, int|string $cacheKey): ResponseInterface
    {
        $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $safeName = str_replace(['"', "\r", "\n"], '', $fileName);

        if (isset(self::INLINE_MIMES[$ext])) {
            return $response
                ->setHeader('Content-Type', self::INLINE_MIMES[$ext])
                ->setHeader('Content-Disposition', 'inline; filename="' . $safeName . '"')
                ->setBody(file_get_contents($path));
        }

        if (in_array($ext, self::BROWSER_RENDERED_EXT, true)) {
            return $this->html($response, $this->browserViewerPage($path, $fileName, $ext));
        }

        $html = match ($ext) {
            'xls'   => $this->cachedHtml($path, $cacheKey, fn () => $this->spreadsheetHtml($path)),
            'doc'   => $this->cachedHtml($path, $cacheKey, fn () => $this->wordHtml($path, 'MsDoc')),
            default => null,
        };

        return $html !== null ? $this->html($response, $html) : $this->unavailable($response);
    }

    /**
     * Server-side HTML renders are slow for big files, so they're cached
     * until the source file changes.
     */
    private function cachedHtml(string $path, int|string $cacheKey, callable $render): ?string
    {
        if (! is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        $cached = rtrim($this->cacheDir, '\\/') . DIRECTORY_SEPARATOR . $cacheKey . '.html';

        if (is_file($cached) && filemtime($cached) >= filemtime($path)) {
            return file_get_contents($cached);
        }

        $html = $render();

        if ($html !== null) {
            file_put_contents($cached, $html);
        }

        return $html;
    }

    private function spreadsheetHtml(string $path): ?string
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $writer      = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);

            ob_start();
            $writer->save('php://output');
            $html = ob_get_clean();
        } catch (\Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }

            return null;
        }

        return $this->withBaseStyles($html);
    }

    /** Lower fidelity than the browser renderers, but better than no preview at all. */
    private function wordHtml(string $path, string $reader): ?string
    {
        try {
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($path, $reader);
            $html    = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML')->getContent();
        } catch (\Throwable $e) {
            return null;
        }

        return $this->withBaseStyles($html);
    }

    private function withBaseStyles(string $html): string
    {
        $extra = '<style>body{font-family:"Inter",system-ui,sans-serif;padding:1.5rem;color:#1f2937;background:#fff;}'
            . 'table{border-collapse:collapse;} table td,table th{border:1px solid #d1d5db;padding:.35rem .5rem;}'
            . 'img{max-width:100%;height:auto;}</style>';

        return str_contains($html, '</head>')
            ? str_replace('</head>', $extra . '</head>', $html)
            : $extra . $html;
    }

    /**
     * The file is embedded rather than fetched: the sandboxed page has an
     * opaque origin, so it couldn't make an authenticated request for it.
     */
    private function browserViewerPage(string $path, string $fileName, string $ext): string
    {
        $title      = esc($fileName);
        $file       = json_encode(['ext' => $ext, 'data' => base64_encode(file_get_contents($path))]);
        $scriptPath = FCPATH . 'assets/js/office-viewer.js';
        $scriptUrl  = base_url('assets/js/office-viewer.js') . '?v=' . (is_file($scriptPath) ? filemtime($scriptPath) : '1');

        return <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title>{$title}</title>
            </head>
            <body>
            <div id="previewMsg"></div>
            <script>window.PREVIEW_FILE = {$file};</script>
            <script src="{$scriptUrl}"></script>
            </body>
            </html>
            HTML;
    }

    private function html(ResponseInterface $response, string $html): ResponseInterface
    {
        return $response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setHeader('Content-Security-Policy', self::SANDBOX_CSP)
            ->setBody($html);
    }

    private function unavailable(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setHeader('X-Preview-Available', '0')
            ->setBody('<div style="font-family:sans-serif;color:#6b7280;text-align:center;padding:3rem 1rem;">'
                . 'Preview isn\'t available for this file type. Download it to view the contents.</div>');
    }
}
