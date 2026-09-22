<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Email layout themes resolved straight from the filesystem:
 * every `*.blade.php` file inside resources/views/emails/templates becomes a
 * selectable theme for an EmailTemplate (the slug is the file name, stored on
 * the template row). To add a theme, copy an existing file and edit its
 * colors — the admin picker picks it up automatically.
 */
class EmailThemes
{
    public static function path(): string
    {
        return resource_path('views/emails/templates');
    }

    /**
     * Every theme file under resources/views/emails/templates, as slug => label.
     * Cache-scan (mirrors App\Support\Themes) so the admin picker and mail
     * resolution don't re-stat the folder on every request.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return Cache::remember('email-themes:all', 86400, fn () => self::scan());
    }

    /**
     * @return array<string, string>
     */
    private static function scan(): array
    {
        if (! is_dir(self::path())) {
            return [];
        }

        return collect(scandir(self::path()))
            ->filter(fn (string $entry): bool => str_ends_with($entry, '.blade.php'))
            ->map(fn (string $entry): string => substr($entry, 0, -10))
            ->sort()
            ->mapWithKeys(fn (string $slug): array => [$slug => ucwords(str_replace(['-', '_'], ' ', $slug))])
            ->all();
    }

    /**
     * The dotted view that should render a given email theme — the theme's own
     * file when it exists, otherwise the "default" theme's file (falling back
     * to the first available theme if default itself is missing).
     */
    public static function view(?string $slug): string
    {
        $available = self::all();
        $slug = $slug ?? 'default';

        if ($available !== [] && array_key_exists($slug, $available)) {
            return 'emails.templates.'.$slug;
        }

        if (array_key_exists('default', $available)) {
            return 'emails.templates.default';
        }

        return array_key_first($available) !== null ? 'emails.templates.'.array_key_first($available) : 'emails.templates.default';
    }
}
