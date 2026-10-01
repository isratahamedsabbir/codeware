<?php

namespace App\Support;

use App\Models\MenuItem;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;

/**
 * The plugin registry — a plugin is a self-contained folder under /plugins:
 *
 *   plugins/{slug}/
 *     plugin.json        required — name, version, description, author, icon, default
 *     index.blade.php    required — the plugin's own management screen
 *     routes.php         optional — extra admin routes, mounted at /plugins/{slug}/…
 *     migrations/        optional — loaded while the plugin is active
 *     …any other views   optional — reachable as plugin-{slug}::name
 *
 * The folder name is the slug. "Installed" means the folder exists and holds the
 * two required files; "active" is the admin's switch (Plugins → Plugin Settings),
 * kept in the `plugins_active` setting. Plugins flagged `"default": true` ship
 * with the app, are always active and cannot be removed.
 */
class Plugins
{
    public const MANIFEST = 'plugin.json';

    public const INDEX = 'index.blade.php';

    public const SETTING = 'plugins_active';

    public const MENU_GROUP = 'Plugins';

    /** @var array<string, array>|null */
    private static ?array $all = null;

    public static function path(): string
    {
        return base_path('plugins');
    }

    public static function flush(): void
    {
        self::$all = null;
    }

    public static function isValidSlug(string $slug): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9_-]*$/', $slug);
    }

    /**
     * Every installed plugin, keyed by slug and sorted by name.
     *
     * @return array<string, array{slug: string, name: string, version: string, description: string, author: string, icon: string, default: bool, path: string, active: bool}>
     */
    public static function all(): array
    {
        if (self::$all !== null) {
            return self::$all;
        }

        $plugins = [];

        foreach (glob(self::path().'/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $slug = basename($dir);

            if (! self::isValidSlug($slug) || ($manifest = self::readManifest($dir, $slug)) === null) {
                continue;
            }

            $plugins[$slug] = $manifest;
        }

        uasort($plugins, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));

        $active = self::activeSlugs();

        foreach ($plugins as $slug => &$plugin) {
            $plugin['active'] = $plugin['default'] || in_array($slug, $active, true);
        }

        return self::$all = $plugins;
    }

    public static function find(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /**
     * @return array<string, array>
     */
    public static function active(): array
    {
        return array_filter(self::all(), fn (array $plugin) => $plugin['active']);
    }

    public static function viewNamespace(string $slug): string
    {
        return 'plugin-'.$slug;
    }

    public static function indexView(string $slug): string
    {
        // Idempotent; covers a plugin installed earlier in this same request,
        // after PluginServiceProvider has already booted.
        if ($plugin = self::find($slug)) {
            View::addNamespace(self::viewNamespace($slug), $plugin['path']);
        }

        return self::viewNamespace($slug).'::index';
    }

    /**
     * Validates a plugin folder (anywhere on disk) and returns its normalized
     * manifest, or null when it is not a usable plugin.
     */
    public static function readManifest(string $dir, string $slug): ?array
    {
        $file = $dir.'/'.self::MANIFEST;

        if (! is_file($file) || ! is_file($dir.'/'.self::INDEX)) {
            return null;
        }

        $json = json_decode((string) file_get_contents($file), true);

        if (! is_array($json) || trim((string) ($json['name'] ?? '')) === '') {
            return null;
        }

        return [
            'slug' => $slug,
            'name' => trim((string) $json['name']),
            'version' => (string) ($json['version'] ?? '1.0.0'),
            'description' => (string) ($json['description'] ?? ''),
            'author' => (string) ($json['author'] ?? ''),
            'icon' => MenuItem::iconExists($json['icon'] ?? null) ? $json['icon'] : 'puzzle-piece',
            'default' => (bool) ($json['default'] ?? false),
            'path' => $dir,
            'active' => false,
        ];
    }

    /**
     * @return list<string>
     */
    private static function activeSlugs(): array
    {
        $raw = Setting::get(self::SETTING, '[]');
        $list = is_array($raw) ? $raw : json_decode((string) $raw, true);

        return is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
    }

    /**
     * View names of the header widgets contributed by active plugins — a plugin
     * ships plugins/{slug}/header.blade.php to put an icon in the admin top bar.
     *
     * @return list<string>
     */
    public static function headerViews(): array
    {
        $views = [];

        foreach (self::active() as $slug => $plugin) {
            if (is_file($plugin['path'].'/header.blade.php')) {
                $views[] = self::viewNamespace($slug).'::header';
            }
        }

        return $views;
    }

    /**
     * A plugin's saved settings, merged over the defaults declared in its
     * plugin.json "settings" key. Stored as JSON in `plugin_{slug}_settings`.
     */
    public static function settings(string $slug): array
    {
        $plugin = self::find($slug);
        $manifest = $plugin ? (json_decode((string) @file_get_contents($plugin['path'].'/'.self::MANIFEST), true) ?: []) : [];
        $defaults = is_array($manifest['settings'] ?? null) ? $manifest['settings'] : [];

        $raw = Setting::get("plugin_{$slug}_settings", '[]');
        $saved = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?: []);

        return array_replace($defaults, array_intersect_key($saved, $defaults));
    }

    public static function saveSettings(string $slug, array $values): void
    {
        $allowed = array_keys(self::settings($slug));

        Setting::set("plugin_{$slug}_settings", json_encode(array_intersect_key($values, array_flip($allowed))));
    }

    public static function setActive(string $slug, bool $active): void
    {
        $plugin = self::find($slug);

        if ($plugin === null || $plugin['default']) {
            return;
        }

        $slugs = array_diff(self::activeSlugs(), [$slug]);

        if ($active) {
            $slugs[] = $slug;
        }

        Setting::set(self::SETTING, json_encode(array_values($slugs)));
        self::flush();
    }

    public static function delete(string $slug): bool
    {
        $plugin = self::find($slug);

        if ($plugin === null || $plugin['default']) {
            return false;
        }

        self::setActive($slug, false);
        File::deleteDirectory($plugin['path']);
        self::flush();

        return true;
    }

    /**
     * Fills the sidebar's Plugins dropdown: one entry per active plugin, then
     * Plugin Settings. The entries are built here rather than stored as menu rows
     * because the set of plugins changes by dropping a folder in, not by editing
     * the menu. A seeded "Plugins" group is reused (so the admin can still move
     * it); otherwise a virtual one is appended.
     *
     * @param  Collection<int, MenuItem>  $menu
     * @return Collection<int, MenuItem>
     */
    public static function extendMenu(Collection $menu): Collection
    {
        if (! Features::enabled('plugins') || ! Gate::allows('access-admin-system')) {
            return $menu;
        }

        $children = collect(self::active())->map(fn (array $plugin) => new MenuItem([
            'label' => $plugin['name'],
            'icon' => $plugin['icon'],
            'url' => route('admin.plugins.show', $plugin['slug']),
        ]))->values();

        $settingsLink = new MenuItem([
            'label' => 'Plugin Settings',
            'icon' => 'squares-plus',
            'route_name' => 'admin.plugin-settings',
        ]);

        // Plugin Settings sits right under Theme Settings. Only when that link is
        // not in the menu for this user does it fall back to the end of the Plugins
        // dropdown. A seeded copy (AdminMenuSeeder) is left where it is.
        $placed = false;

        foreach ($menu->filter->is_group as $item) {
            $routes = $item->children->pluck('route_name');

            if ($routes->contains('admin.plugin-settings')) {
                $placed = true;
                break;
            }

            $theme = $routes->search('admin.theme-settings');

            if ($theme !== false) {
                $list = $item->children->values()->all();
                array_splice($list, $theme + 1, 0, [$settingsLink]);
                $item->setRelation('children', collect($list));
                $placed = true;
                break;
            }
        }

        if (! $placed) {
            $children->push($settingsLink);
        }

        if ($children->isEmpty()) {
            return $menu->values();
        }

        $group = $menu->first(fn (MenuItem $item) => $item->is_group && $item->label === self::MENU_GROUP);

        if ($group === null) {
            $group = new MenuItem(['label' => self::MENU_GROUP, 'is_group' => true]);
            $group->id = -1;
            $menu = $menu->push($group);
        }

        $group->setRelation('children', $children);

        return $menu->values();
    }

    /**
     * Extracts a plugin zip and installs it. Returns the new slug.
     *
     * @throws \RuntimeException with a message safe to show the admin
     */
    public static function installFromZip(string $zipPath): string
    {
        $zip = new \ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('This file is not a valid zip plugin package.');
        }

        $temp = tempnam(sys_get_temp_dir(), 'plugin-install-');
        @unlink($temp);
        File::makeDirectory($temp, 0777, true, true);

        try {
            self::extract($zip, $temp);
            $zip->close();
            $zip = null;

            [$source, $slug] = self::locatePackage($temp);

            if (self::readManifest($source, $slug) === null) {
                throw new \RuntimeException('The package must contain a valid '.self::MANIFEST.' (with a "name") and an '.self::INDEX.'.');
            }

            File::ensureDirectoryExists(self::path());

            if (file_exists(self::path().'/'.$slug)) {
                throw new \RuntimeException("A plugin named \"{$slug}\" is already installed.");
            }

            File::moveDirectory($source, self::path().'/'.$slug);
            self::flush();

            return $slug;
        } finally {
            $zip?->close();
            File::deleteDirectory($temp);
        }
    }

    /**
     * A package is either one folder holding plugin.json, or plugin.json at the
     * zip root (the slug then comes from its "slug" key).
     *
     * @return array{0: string, 1: string}
     */
    private static function locatePackage(string $temp): array
    {
        $entries = array_values(array_filter(
            scandir($temp) ?: [],
            fn (string $e) => ! in_array($e, ['.', '..', '__MACOSX'], true) && ! str_starts_with($e, '.')
        ));

        if (count($entries) === 1 && is_dir($temp.'/'.$entries[0])) {
            $slug = self::slugify($entries[0]);

            if ($slug === '') {
                throw new \RuntimeException('The plugin folder name could not be turned into a valid slug (letters, numbers, dashes and underscores).');
            }

            return [$temp.'/'.$entries[0], $slug];
        }

        if (is_file($temp.'/'.self::MANIFEST)) {
            $json = json_decode((string) file_get_contents($temp.'/'.self::MANIFEST), true);
            $slug = self::slugify((string) ($json['slug'] ?? $json['name'] ?? ''));

            if ($slug === '') {
                throw new \RuntimeException('Add a "slug" to '.self::MANIFEST.' or put the files inside a single folder named after the plugin.');
            }

            return [$temp, $slug];
        }

        throw new \RuntimeException('The zip must contain exactly one plugin folder at its root.');
    }

    private static function slugify(string $name): string
    {
        return trim(preg_replace('/[^a-z0-9_-]+/', '-', strtolower($name)), '-_');
    }

    private static function extract(\ZipArchive $zip, string $temp): void
    {
        $tempRoot = realpath($temp);
        $total = 0;

        if ($zip->numFiles > 2000) {
            throw new \RuntimeException('The zip contains too many files to be a plugin (max 2000).');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));

            if ($name === '' || str_starts_with($name, '__MACOSX/')) {
                continue;
            }

            if (preg_match('#(^|/)\.\.(/|$)#', $name) || str_starts_with($name, '/') || preg_match('#^[A-Za-z]:#', $name)) {
                throw new \RuntimeException('The zip contains unsafe file paths.');
            }

            $total += (int) $zip->statIndex($i)['size'];

            if ($total > 52428800) {
                throw new \RuntimeException('The plugin package is too large (max 50MB).');
            }

            $target = $temp.'/'.$name;

            if (str_ends_with($name, '/')) {
                File::makeDirectory($target, 0777, true, true);

                continue;
            }

            File::makeDirectory(dirname($target), 0777, true, true);

            $realDir = realpath(dirname($target));

            if ($realDir === false || ! str_starts_with($realDir, $tempRoot)) {
                throw new \RuntimeException('The zip contains unsafe file paths.');
            }

            file_put_contents($target, (string) $zip->getFromIndex($i));
        }
    }
}
