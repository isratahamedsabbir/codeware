<?php

namespace App\Http\Controllers;

use App\Support\Themes;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves a file out of a theme's assets/ folder at /themes/{slug}/{path}.
 *
 * A theme is one folder under themes/, outside public/, so the only part of it
 * the web may reach is assets/ — and it reaches it through here. Nothing else in
 * the folder (templates, controllers, theme.json) is ever served, and the
 * resolved path must stay inside assets/ so "../" cannot climb out of it.
 */
class ThemeAssetController extends Controller
{
    private const TYPES = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'mjs' => 'text/javascript; charset=utf-8',
        'json' => 'application/json',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'ico' => 'image/x-icon',
    ];

    public function __invoke(Request $request, string $theme, string $path): BinaryFileResponse
    {
        abort_unless(array_key_exists($theme, Themes::all()), 404);

        $root = realpath(Themes::assetsPath($theme));
        $file = $root === false ? false : realpath($root.'/'.$path);

        abort_if(
            $file === false
            || ! is_file($file)
            || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR),
            404
        );

        $type = self::TYPES[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? null;

        // Only known static types go out; an unlisted extension is not an asset.
        abort_if($type === null, 404);

        return response()->file($file, [
            'Content-Type' => $type,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
}
