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
        // Linux servers (apt/dnf packages, snap, official .deb/.rpm in /opt)
        '/usr/bin/soffice',
        '/usr/bin/libreoffice',
        '/usr/lib/libreoffice/program/soffice',
        '/snap/bin/libreoffice',
        '/opt/libreoffice/program/soffice',
        // macOS
        '/Applications/LibreOffice.app/Contents/MacOS/soffice',
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
        // Explicit override, e.g. `libreoffice.path = /opt/libreoffice25.2/program/soffice` in .env.
        $configured = (string) env('libreoffice.path', '');
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        foreach (self::CANDIDATE_PATHS as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        // Versioned /opt installs (/opt/libreoffice7.6/program/soffice, ...).
        foreach (glob('/opt/libreoffice*/program/soffice') ?: [] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
