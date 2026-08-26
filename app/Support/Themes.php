<?php

namespace App\Support;

use App\Models\Setting;

class Themes
{
    public static function path(): string
    {
        return resource_path('views/frontend/themes');
    }

    /**
     * Every theme folder under resources/views/frontend/themes, as slug => label.
     *
     * @return array<string, string>
     */
    public static function all(): array
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
}
