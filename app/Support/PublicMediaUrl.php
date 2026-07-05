<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Builds absolute URLs for files on the public disk so SPA / mobile clients
 * can load images even when APP_URL in .env omits the port (e.g. http://localhost
 * while the API runs on :8000). Prefer the current HTTP request root when available.
 */
final class PublicMediaUrl
{
    private function __construct() {}

    /**
     * Full URL for a path returned by Storage::disk('public'), e.g. "avatars/abc.jpg".
     */
    public static function absolute(string $pathOnPublicDisk): string
    {
        $relative = ltrim(str_replace('\\', '/', $pathOnPublicDisk), '/');

        if (! app()->runningInConsole() && request()) {
            try {
                return rtrim(request()->root(), '/').'/storage/'.$relative;
            } catch (\Throwable) {
                // fall through
            }
        }

        return Storage::disk('public')->url($relative);
    }

    /**
     * Rewrites stored URLs that point at /storage/... to use the current request root,
     * fixing rows saved with asset() / wrong APP_URL (wrong port or host).
     */
    public static function normalize(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        if (app()->runningInConsole() || ! request()) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '' || ! str_contains($path, '/storage/')) {
            return $url;
        }

        try {
            return rtrim(request()->root(), '/').$path;
        } catch (\Throwable) {
            return $url;
        }
    }
}
