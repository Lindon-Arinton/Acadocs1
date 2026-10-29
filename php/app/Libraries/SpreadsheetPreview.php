<?php

namespace App\Libraries;

/**
 * Renders a spreadsheet (xls, xlsx, csv, ods) as a standalone HTML page with
 * PhpSpreadsheet — no LibreOffice and no outside service, so previews also
 * work on shared hosting (Hostinger can't run LibreOffice). Every sheet is
 * shown, with links to jump between them; images are embedded.
 */
class SpreadsheetPreview
{
    public const EXTENSIONS = ['xls', 'xlsx', 'csv', 'ods'];

    /** Bigger workbooks can run PHP out of memory; those are download-only. */
    private const MAX_BYTES = 15_000_000;

    public static function supports(string $fileName): bool
    {
        return in_array(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)), self::EXTENSIONS, true);
    }

    /** The HTML page, or null when the file is too big or can't be read (corrupted, password-protected…). */
    public static function html(string $path): ?string
    {
        if (! is_file($path) || filesize($path) > self::MAX_BYTES) {
            return null;
        }

        $level = ob_get_level();
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $writer      = new \PhpOffice\PhpSpreadsheet\Writer\Html($spreadsheet);
            $writer->writeAllSheets();
            $writer->setEmbedImages(true);

            ob_start();
            $writer->save('php://output');
            $html = ob_get_clean();
            $spreadsheet->disconnectWorksheets();
        } catch (\Throwable $e) {
            // Only drop the buffer opened here, not the framework's own.
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            log_message('error', 'Spreadsheet preview failed for {path}: {msg}', ['path' => basename($path), 'msg' => $e->getMessage()]);

            return null;
        }

        $extra = '<style>body{font-family:"Inter",system-ui,sans-serif;padding:1.5rem;color:#1f2937;}'
            . 'table{border-collapse:collapse;} table td,table th{border:1px solid #d1d5db;padding:.35rem .5rem;}'
            . 'img{max-width:100%;height:auto;}'
            . 'ul.navigation{list-style:none;display:flex;flex-wrap:wrap;gap:.4rem;padding:0;margin:0 0 1rem;}'
            . 'ul.navigation a{display:inline-block;padding:.3rem .8rem;border:1px solid #d1d5db;border-radius:999px;color:#800000;text-decoration:none;font-size:.85rem;}'
            . '</style>';

        return str_contains($html, '</head>')
            ? str_replace('</head>', $extra . '</head>', $html)
            : $extra . $html;
    }
}
