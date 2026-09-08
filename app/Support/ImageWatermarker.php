<?php

namespace App\Support;

use App\Models\Setting;
use GdImage;
use Illuminate\Support\Facades\Storage;

/**
 * Stamps the configured watermark image onto a just-uploaded Media Library
 * image, in place, when the "watermark_enabled" setting is on. Uses plain
 * GD (already relied on elsewhere, e.g. UserCardController) rather than
 * pulling in an image library just for this.
 */
class ImageWatermarker
{
    private const WATERMARKABLE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public static function applyIfEnabled(string $disk, string $path, string $mimeType): void
    {
        if (! Setting::get('watermark_enabled')) {
            return;
        }

        if (! in_array($mimeType, self::WATERMARKABLE_MIMES, true)) {
            return;
        }

        $watermarkPath = self::resolveLocalPath((string) Setting::get('watermark_image'));
        $targetPath = Storage::disk($disk)->path($path);

        if (! $watermarkPath || ! is_file($targetPath)) {
            return;
        }

        self::stamp($targetPath, $mimeType, $watermarkPath);
    }

    /**
     * The "watermark_image" setting holds a public URL (same convention as
     * site_icon/favicon/etc, picked via <x-media-picker>) — resolve it back
     * to a local filesystem path on the public disk to load with GD.
     */
    private static function resolveLocalPath(string $url): ?string
    {
        if ($url === '') {
            return null;
        }

        $urlPath = parse_url($url, PHP_URL_PATH) ?: $url;
        $marker = '/storage/';
        $pos = strpos($urlPath, $marker);

        if ($pos === false) {
            return null;
        }

        $relative = substr($urlPath, $pos + strlen($marker));

        return Storage::disk('public')->exists($relative)
            ? Storage::disk('public')->path($relative)
            : null;
    }

    private static function stamp(string $targetPath, string $mimeType, string $watermarkPath): void
    {
        $base = self::load($targetPath, $mimeType);
        $mark = self::load($watermarkPath, self::mimeFromExtension($watermarkPath));

        if (! $base || ! $mark) {
            return;
        }

        $baseWidth = imagesx($base);
        $baseHeight = imagesy($base);
        $markWidth = imagesx($mark);
        $markHeight = imagesy($mark);

        // Cap the watermark at 20% of the base image's width — a full-size
        // logo would swamp a small thumbnail. Aspect ratio is preserved.
        $maxWidth = max(20, (int) ($baseWidth * 0.2));

        if ($markWidth > $maxWidth) {
            $scale = $maxWidth / $markWidth;
            $newWidth = $maxWidth;
            $newHeight = max(1, (int) round($markHeight * $scale));

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $mark, 0, 0, 0, 0, $newWidth, $newHeight, $markWidth, $markHeight);
            imagedestroy($mark);

            $mark = $resized;
            $markWidth = $newWidth;
            $markHeight = $newHeight;
        }

        $margin = max(8, (int) ($baseWidth * 0.02));
        [$x, $y] = self::position(
            (string) Setting::get('watermark_position', 'bottom-right'),
            $baseWidth,
            $baseHeight,
            $markWidth,
            $markHeight,
            $margin,
        );

        $opacity = max(0, min(100, (int) Setting::get('watermark_opacity', 50)));

        self::mergeWithAlpha($base, $mark, $x, $y, $markWidth, $markHeight, $opacity);
        self::save($base, $targetPath, $mimeType);

        imagedestroy($base);
        imagedestroy($mark);
    }

    private static function load(string $path, string $mimeType): ?GdImage
    {
        $image = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
            default => false,
        };

        if (! $image instanceof GdImage) {
            return null;
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    private static function save(GdImage $image, string $path, string $mimeType): void
    {
        match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $path, 90),
            'image/png' => imagepng($image, $path, 6),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $path, 90) : null,
            default => null,
        };
    }

    private static function mimeFromExtension(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/png',
        };
    }

    /**
     * @return array{int, int}
     */
    private static function position(string $position, int $baseWidth, int $baseHeight, int $markWidth, int $markHeight, int $margin): array
    {
        return match ($position) {
            'top-left' => [$margin, $margin],
            'top-right' => [$baseWidth - $markWidth - $margin, $margin],
            'bottom-left' => [$margin, $baseHeight - $markHeight - $margin],
            'center' => [(int) (($baseWidth - $markWidth) / 2), (int) (($baseHeight - $markHeight) / 2)],
            default => [$baseWidth - $markWidth - $margin, $baseHeight - $markHeight - $margin],
        };
    }

    /**
     * imagecopymerge() ignores the source image's own alpha channel, so a
     * semi-transparent PNG watermark would otherwise get its transparent
     * areas rendered as solid black. This is the standard workaround: merge
     * onto a copy of the destination region first, then blend that back in
     * at the requested opacity.
     */
    private static function mergeWithAlpha(GdImage $dst, GdImage $src, int $dstX, int $dstY, int $width, int $height, int $opacity): void
    {
        $cut = imagecreatetruecolor($width, $height);
        imagecopy($cut, $dst, 0, 0, $dstX, $dstY, $width, $height);
        imagecopy($cut, $src, 0, 0, 0, 0, $width, $height);
        imagecopymerge($dst, $cut, $dstX, $dstY, 0, 0, $width, $height, $opacity);
        imagedestroy($cut);
    }
}
