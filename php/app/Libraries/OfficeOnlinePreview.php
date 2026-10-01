<?php

namespace App\Libraries;

/**
 * Fallback preview through Microsoft's Office Online viewer, used only when
 * LibreOffice (OfficeConverter) can't render a file on this server.
 *
 * The viewer downloads the file itself, from Microsoft's servers, so it needs
 * a URL that is (1) on the public internet and (2) works without logging in.
 * Files here sit behind authGuard, so this hands the viewer a short-lived
 * signed link (see PublicFile controller) instead of the normal file URL.
 *
 * Disabled automatically when the site runs on localhost / a LAN address
 * (Microsoft can't reach it), and can be switched off entirely with
 * `preview.officeOnline = false` in .env — previewed documents are sent to
 * Microsoft, so a school may choose not to allow that.
 */
class OfficeOnlinePreview
{
    private const VIEWER = 'https://view.officeapps.live.com/op/embed.aspx?src=';

    /** Office Online size limits (bytes): Word/PowerPoint ~10 MB, Excel ~5 MB. */
    private const MAX_BYTES = [
        'doc' => 10_000_000, 'docx' => 10_000_000,
        'ppt' => 10_000_000, 'pptx' => 10_000_000,
        'xls' => 5_000_000,  'xlsx' => 5_000_000,
    ];

    /** How long a signed link stays valid; the viewer fetches the file right away. */
    private const LINK_TTL = 1800;

    /**
     * Embed URL for the viewer, or null when this file can't / shouldn't use it.
     *
     * @param string $kind 'template' or 'docfile' — which table $id refers to
     */
    public static function embedUrl(string $kind, int $id, string $filePath, string $fileName): ?string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (! self::enabled() || ! isset(self::MAX_BYTES[$ext]) || ! is_file($filePath)) {
            return null;
        }

        if (filesize($filePath) > self::MAX_BYTES[$ext]) {
            return null;
        }

        return self::VIEWER . rawurlencode(self::signedUrl($kind, $id, $fileName));
    }

    public static function enabled(): bool
    {
        $setting = env('preview.officeOnline', true);
        if ($setting === false || in_array(strtolower((string) $setting), ['false', '0', 'off', 'no'], true)) {
            return false;
        }

        return self::isPubliclyReachable(config('App')->baseURL);
    }

    /** Signed, expiring public link to the raw file; the file name (with extension) ends the URL so the viewer knows the type. */
    public static function signedUrl(string $kind, int $id, string $fileName): string
    {
        $expires = time() + self::LINK_TTL;
        $safe    = preg_replace('/[^A-Za-z0-9._-]+/', '_', $fileName);

        return base_url("public-file/{$kind}/{$id}/{$expires}/" . self::signature($kind, $id, $expires) . '/' . $safe);
    }

    public static function verify(string $kind, int $id, int $expires, string $signature): bool
    {
        return $expires >= time() && hash_equals(self::signature($kind, $id, $expires), $signature);
    }

    private static function signature(string $kind, int $id, int $expires): string
    {
        return substr(hash_hmac('sha256', "{$kind}|{$id}|{$expires}", self::secret()), 0, 40);
    }

    /** encryption.key from .env if set, else a random key generated once into writable/ (gitignored). */
    private static function secret(): string
    {
        $key = (string) config('Encryption')->key;
        if ($key !== '') {
            return $key;
        }

        $file = WRITEPATH . 'preview_link.key';
        if (! is_file($file)) {
            file_put_contents($file, bin2hex(random_bytes(32)), LOCK_EX);
            @chmod($file, 0600);
        }

        return trim((string) file_get_contents($file));
    }

    /** False for localhost, LAN/private addresses and dot-less intranet names: Microsoft's servers can't fetch from those. */
    private static function isPubliclyReachable(string $baseUrl): bool
    {
        $host = strtolower((string) parse_url($baseUrl, PHP_URL_HOST));
        $host = trim($host, '[]');

        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.localhost')) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return (bool) filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return str_contains($host, '.');
    }
}
