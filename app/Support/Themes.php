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
