<?php

namespace App\Support;

/**
 * The panel's typeface, discovered from the filesystem.
 *
 * The default is SYSTEM: the OS answers, so the setting costs no request. Every
 * other option is a sub-folder of public/fonts/, one folder per family:
 *
 *   public/fonts/inter/inter-latin.woff2
 *   public/fonts/inter/inter-latin-ext.woff2
 *   public/fonts/hind-siliguri/HindSiliguri-Bold.ttf
 *
 * Dropping a folder in is the whole installation: it appears in the Backend Font
 * dropdown, and selecting it declares an @font-face for each file inside and
 * puts the family ahead of the system stack. The same folders are offered to the
 * storefront themes (see ThemeFont), so a font is stored once; which font the
 * panel uses and which each theme uses are still separate settings.
 * A saved font whose folder has since been deleted falls back to the system font.
 *
 * Conventions read from file names (all optional):
 *   - weight: thin, extralight, light, regular, medium, semibold, bold,
 *     extrabold, black, or a number 100-900. No hint means a variable font
 *     covering 100-900.
 *   - style: "italic" or "oblique" in the name.
 *   - subset: "latin-ext" or "latin" in the name sets the Google unicode-range,
 *     so the ext file is only fetched when a character needs it.
 *
 * The stored value is the folder name. Folder names are restricted to letters,
 * digits, space, "_" and "-", which is what lets the layout print the family and
 * URLs unescaped inside <style> without any stored text reaching the CSS.
 *
 * @see resources/views/layouts/admin.blade.php
 */
class AdminFont
{
    /**
     * The null option: the OS's own UI font.
     */
    public const SYSTEM = 'system';

    /**
     * Where font folders live, relative to public/fonts/. Empty: the folders sit
     * directly in public/fonts/ and are shared by the admin panel and every
     * storefront theme, so one copy of a font serves all of them.
     */
    public const SCOPE = '';

    private const FOLDER_PATTERN = '/^[A-Za-z0-9][A-Za-z0-9 _-]*$/';

    private const FORMATS = [
        'woff2' => 'woff2',
        'woff' => 'woff',
        'ttf' => 'truetype',
        'otf' => 'opentype',
    ];

    private const WEIGHTS = [
        'extralight' => 200, 'ultralight' => 200,
        'extrabold' => 800, 'ultrabold' => 800,
        'semibold' => 600, 'demibold' => 600,
        'thin' => 100, 'light' => 300, 'regular' => 400, 'normal' => 400,
        'medium' => 500, 'bold' => 700, 'black' => 900, 'heavy' => 900,
    ];

    private const RANGES = [
        'latin-ext' => 'U+0100-02BA, U+02BD-02C5, U+02C7-02CC, U+02CE-02D7, U+02DD-02FF, U+0304, U+0308, U+0329, U+1D00-1DBF, U+1E00-1E9F, U+1EF2-1EFF, U+2020, U+20A0-20AB, U+20AD-20C0, U+2113, U+2C60-2C7F, U+A720-A7FF',
        'latin' => 'U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD',
    ];

    private const SYSTEM_STACK = "ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";

    /**
     * The label each stored value is offered under: the system font first, then
     * every font folder, alphabetically.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [self::SYSTEM => 'System font'] + self::folders();
    }

    /**
     * Just the font folders under public/fonts/{scope}/: folder name => label.
     *
     * Public so the storefront's ThemeFont can offer the same folders.
     *
     * @return array<string, string>
     */
    public static function folders(string $scope = self::SCOPE): array
    {
        $folders = [];

        foreach (glob(rtrim(public_path('fonts/'.$scope), '/\\').'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);

            if ($name !== self::SYSTEM && preg_match(self::FOLDER_PATTERN, $name) && self::filesIn($dir) !== []) {
                $folders[$name] = ucwords(str_replace(['-', '_'], ' ', $name));
            }
        }

        return $folders;
    }

    /**
     * The stack for each option, keyed by stored value.
     *
     * @return array<string, string>
     */
    public static function stacks(): array
    {
        $stacks = [];

        foreach (array_keys(self::options()) as $value) {
            $stacks[$value] = self::stackFor($value);
        }

        return $stacks;
    }

    /**
     * The stack to apply for a stored value; unknown values get the system stack.
     *
     * @return non-empty-string
     */
    public static function stackFor(mixed $value): string
    {
        $value = self::normalize($value);

        return $value === self::SYSTEM ? self::SYSTEM_STACK : "'{$value}', ".self::SYSTEM_STACK;
    }

    /**
     * The value to store, normalised against the folders that exist.
     */
    public static function normalize(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return $value !== self::SYSTEM && array_key_exists($value, self::options()) ? $value : self::SYSTEM;
    }

    /**
     * The @font-face descriptors for an option; empty for the system font.
     *
     * @return list<array{family: string, url: string, format: string, weight: string, style: string, unicodeRange: ?string}>
     */
    public static function facesFor(mixed $value): array
    {
        $value = self::normalize($value);

        return $value === self::SYSTEM ? [] : self::facesIn(self::SCOPE, $value);
    }

    /**
     * The @font-face descriptors for one folder of a scope. The folder name must
     * already be a known one (see folders()).
     *
     * @return list<array{family: string, url: string, format: string, weight: string, style: string, unicodeRange: ?string}>
     */
    public static function facesIn(string $scope, string $value): array
    {
        $faces = [];

        foreach (self::filesIn(rtrim(public_path('fonts/'.$scope), '/\\').'/'.$value) as $path) {
            $file = basename($path);
            $name = strtolower(pathinfo($file, PATHINFO_FILENAME));

            $faces[] = [
                'family' => $value,
                'url' => '/fonts/'.implode('/', array_map('rawurlencode', [...array_filter(explode('/', $scope)), $value, $file])),
                'format' => self::FORMATS[strtolower(pathinfo($file, PATHINFO_EXTENSION))],
                'weight' => self::weightOf($name),
                'style' => preg_match('/italic|oblique/', $name) ? 'italic' : 'normal',
                'unicodeRange' => str_contains($name, 'latin-ext') ? self::RANGES['latin-ext']
                    : (str_contains($name, 'latin') ? self::RANGES['latin'] : null),
            ];
        }

        return $faces;
    }

    /**
     * The one file worth preloading: a regular-style woff2, the plain latin
     * subset if there is one. Null when there is no suitable woff2.
     */
    public static function preloadFor(mixed $value): ?string
    {
        return self::preloadOf(self::facesFor($value));
    }

    /**
     * @param  list<array{url: string, format: string, style: string, unicodeRange: ?string}>  $faces
     */
    public static function preloadOf(array $faces): ?string
    {
        $candidates = array_values(array_filter(
            $faces,
            fn (array $f) => $f['format'] === 'woff2' && $f['style'] === 'normal',
        ));

        foreach ($candidates as $face) {
            if ($face['unicodeRange'] === self::RANGES['latin']) {
                return $face['url'];
            }
        }

        foreach ($candidates as $face) {
            if ($face['unicodeRange'] === null) {
                return $face['url'];
            }
        }

        return null;
    }

    /**
     * @return list<string> Font files directly inside a folder, sorted.
     */
    private static function filesIn(string $dir): array
    {
        $files = array_filter(
            glob($dir.'/*') ?: [],
            fn (string $p) => is_file($p) && isset(self::FORMATS[strtolower(pathinfo($p, PATHINFO_EXTENSION))]),
        );

        sort($files);

        return array_values($files);
    }

    private static function weightOf(string $name): string
    {
        foreach (self::WEIGHTS as $word => $weight) {
            if (str_contains($name, $word)) {
                return (string) $weight;
            }
        }

        if (preg_match('/(?<!\d)([1-9]00)(?!\d)/', $name, $m)) {
            return $m[1];
        }

        return '100 900';
    }
}
