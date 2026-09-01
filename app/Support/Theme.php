<?php

namespace App\Support;

use App\Models\Setting;

class Theme
{
    /**
     * Ready-made admin panel color themes — each bundles a mode and an accent color
     * so the Settings → Theme tab can offer one-click presets, in addition to
     * manually picking mode/accent/name separately.
     *
     * @var array<int, array{name: string, mode: string, accent: string}>
     */
    public const PRESETS = [
        ['name' => 'Ocean Blue', 'mode' => 'light', 'accent' => '#2563eb'],
        ['name' => 'Forest Green', 'mode' => 'light', 'accent' => '#7cc242'],
        ['name' => 'Sunset Orange', 'mode' => 'light', 'accent' => '#ea580c'],
        ['name' => 'Berry Pink', 'mode' => 'light', 'accent' => '#db2777'],
        ['name' => 'Royal Purple', 'mode' => 'light', 'accent' => '#7c3aed'],
        ['name' => 'Midnight', 'mode' => 'dark', 'accent' => '#2563eb'],
        ['name' => 'Dark Teal', 'mode' => 'dark', 'accent' => '#0d9488'],
        ['name' => 'Crimson Night', 'mode' => 'dark', 'accent' => '#dc2626'],
    ];

    public static function mode(): string
    {
        return Setting::get('theme_mode', 'light');
    }

    public static function isDark(): bool
    {
        return static::mode() === 'dark';
    }

    public static function accent(): string
    {
        return Setting::get('theme_accent', '#1e7bc4');
    }

    public static function name(): string
    {
        return Setting::get('theme_name', 'Default');
    }
}
