<?php

namespace App\Support;

use App\Models\Setting;

class Theme
{
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
