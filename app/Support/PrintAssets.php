<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * Shared by every DomPDF-rendered document (invoices, product labels, ...).
 */
class PrintAssets
{
    /**
     * Embedded as a data URI (rather than an <img src="..."> URL) so it renders
     * identically in the browser and in dompdf, which has remote image fetching
     * disabled by default. Prefers the admin-configured site icon, resolved back
     * to a local file on the public disk; falls back to the bundled logo.
     */
    public static function logoDataUri(): ?string
    {
        $siteIcon = Setting::get('site_icon');

        if ($siteIcon && ($path = self::publicDiskPath($siteIcon)) && is_file($path)) {
            return self::fileToDataUri($path);
        }

        $path = public_path('default/logo.png');

        return is_file($path) ? self::fileToDataUri($path) : null;
    }

    /**
     * Resolves a public-disk URL (relative or absolute) back to the local
     * file it was uploaded to, or null if it isn't one.
     */
    private static function publicDiskPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;

        if (! str_starts_with($path, '/storage/')) {
            return null;
        }

        return Storage::disk('public')->path(substr($path, strlen('/storage/')));
    }

    private static function fileToDataUri(string $path): string
    {
        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(file_get_contents($path));
    }
}
