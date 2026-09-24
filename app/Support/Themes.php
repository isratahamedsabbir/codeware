<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class Themes
{
    /**
     * Request-scoped memo of the cached folder scan — active()/view() are hit
     * several times a themed page. Keyed by the cache repository instance so
     * the memo dies with the bootstrap that owns the cache.
     */
    private static array $all = [];

    public static function path(): string
    {
        return resource_path('views/frontend/themes');
    }

    /**
     * Every theme folder under resources/views/frontend/themes, as slug => label.
     *
     * The folder list is a filesystem scan (scandir + is_dir per entry), so it's
     * cached for a day rather than repeated on every themed request — the admin
     * re-reads it live inside its own picker cache, and the folder list almost
     * never changes outside a deployment.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $key = spl_object_id(Cache::getFacadeRoot());

        return self::$all[$key] ??= Cache::remember('themes:all', 86400, fn () => self::scan());
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
            ->filter(fn ($entry) => ! in_array($entry, ['.', '..'], true) && is_dir(self::path().'/'.$entry))
            ->sort()
            ->mapWithKeys(fn ($slug) => [$slug => ucwords(str_replace(['-', '_'], ' ', $slug))])
            ->all();
    }

    /**
     * Drop the cached theme list so installs/uninstalls show up on the next
     * call to all() — called by the admin theme installer after it writes a
     * new folder, since all() otherwise caches the scan for a whole day.
     */
    public static function forget(): void
    {
        self::$all = [];
        Cache::forget('themes:all');
    }

    /**
     * A theme's own settings, read from a theme.json manifest file at the
     * folder's root. A manifest is optional — when a theme ships none, the
     * slug-derived label, no author and version "1.0.0" are returned instead,
     * so every theme (installed or built-in) has a well-formed manifest.
     *
     * @return array{name: string, description: string, version: string, author: string, tags: array<int, string>}
     */
    public static function manifest(string $slug): array
    {
        $defaults = [
            'name' => ucwords(str_replace(['-', '_'], ' ', $slug)),
            'description' => '',
            'version' => '1.0.0',
            'author' => '',
            'tags' => [],
        ];

        $file = self::path().'/'.$slug.'/theme.json';

        if (! is_file($file)) {
            return $defaults;
        }

        $data = json_decode((string) file_get_contents($file), true);

        if (! is_array($data)) {
            return $defaults;
        }

        return [
            'name' => is_string($data['name'] ?? null) && $data['name'] !== '' ? $data['name'] : $defaults['name'],
            'description' => is_string($data['description'] ?? null) ? $data['description'] : '',
            'version' => is_string($data['version'] ?? null) && $data['version'] !== '' ? $data['version'] : $defaults['version'],
            'author' => is_string($data['author'] ?? null) ? $data['author'] : '',
            'tags' => collect($data['tags'] ?? [])->filter(fn ($tag) => is_string($tag) && $tag !== '')->values()->all(),
        ];
    }

    /**
     * Whether a theme ships its own settings screen — i.e. a settings.blade.php
     * at the folder's root. The Theme Settings admin screen renders that view
     * inline whenever the theme is the one selected in the picker.
     */
    public static function hasSettings(string $slug): bool
    {
        return is_file(self::path().'/'.$slug.'/settings.blade.php');
    }

    /**
     * The theme to render at the public root URL — the admin-selected theme,
     * falling back to "default" (or the first available theme) if the selected
     * theme's folder no longer exists.
     */
    public static function active(): string
    {
        $available = self::all();
        $selected = Setting::get('site_theme', 'default');

        if (array_key_exists($selected, $available)) {
            return $selected;
        }

        return array_key_exists('default', $available) ? 'default' : (array_key_first($available) ?? 'default');
    }

    /**
     * The dotted view path that should render a given storefront view — the
     * active theme's when it ships that view itself, otherwise the "ecommerce"
     * theme's version (which every theme falls back to for the product/shop
     * pages). Returns e.g. "ecommerce.shop" for callers to build
     * "frontend.themes.ecommerce.shop" from.
     *
     * Used by the storefront routes only; home()/page() keep rendering the
     * active theme's own templates directly, so a "portfolio" or "default"
     * site's home/about/contact pages are untouched by this.
     */
    public static function view(string $name): string
    {
        $theme = self::active();

        if (is_file(self::path().'/'.$theme.'/'.$name.'.blade.php')) {
            return $theme.'.'.$name;
        }

        return is_file(self::path().'/ecommerce/'.$name.'.blade.php') ? 'ecommerce.'.$name : $theme.'.'.$name;
    }
}
