<?php

namespace App\Libraries;

/**
 * Wraps LibreOffice's headless `--convert-to` command so office documents
 * can be converted to PDF (for preview / download) and PDFs back to Word.
 */
class OfficeConverter
{
    private const CANDIDATE_PATHS = [
        'C:\\Program Files\\LibreOffice\\program\\soffice.com',
        'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.com',
    ];

    private ?string $sofficePath;

    public function __construct()
    {
        $this->sofficePath = $this->locateSoffice();
    }

    public function isAvailable(): bool
    {
        return $this->sofficePath !== null;
    }

    /**
     * Converts $sourcePath to $targetExt inside $outputDir.
     * Returns the resulting file path, or null if conversion failed / soffice is unavailable.
     */
    public function convert(string $sourcePath, string $targetExt, string $outputDir): ?string
    {
        if (! $this->sofficePath || ! is_file($sourcePath)) {
            return null;
        }

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $cmd = [
            $this->sofficePath,
            '--headless',
            '--norestore',
            '--convert-to', $targetExt,
            '--outdir', $outputDir,
            $sourcePath,
        ];

        $descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $process     = proc_open($cmd, $descriptors, $pipes);

        if (! is_resource($process)) {
            return null;
        }

        fclose($pipes[0]);

        $start   = time();
        $timeout = 60;
        $status  = proc_get_status($process);

        while ($status['running'] && (time() - $start) < $timeout) {
            usleep(200000);
            $status = proc_get_status($process);
        }

        if ($status['running']) {
            proc_terminate($process);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $expected = rtrim($outputDir, '\\/') . DIRECTORY_SEPARATOR
            . pathinfo($sourcePath, PATHINFO_FILENAME) . '.' . $targetExt;

        return is_file($expected) ? $expected : null;
    }

    private function locateSoffice(): ?string
    {
        foreach (self::CANDIDATE_PATHS as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
