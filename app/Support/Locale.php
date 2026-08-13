<?php

namespace App\Support;

use App\Models\Language;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Throwable;

/**
 * The active locale is a global setting (like Theme) — one admin switching the language
 * switches it for everyone.
 */
class Locale
{
    /**
     * The locale the application is currently rendering in.
     */
    public static function current(): string
    {
        return App::getLocale();
    }

    /**
     * The configured locale: the `app_locale` setting, falling back to the language row
     * flagged default, then to config.
     */
    public static function default(): string
    {
        try {
            $stored = Setting::get('app_locale');

            if ($stored && static::isSupported($stored)) {
                return $stored;
            }

            $flagged = static::active()->firstWhere('is_default', true)?->code;

            if ($flagged) {
                return $flagged;
            }
        } catch (Throwable) {
            // Settings/languages table not migrated yet — fall through to config.
        }

        return config('app.locale');
    }

    /**
     * Switch the application locale for every user.
     */
    public static function set(string $code): void
    {
        if (! static::isSupported($code)) {
            return;
        }

        Setting::set('app_locale', $code);

        App::setLocale($code);
    }

    /**
     * @return Collection<int, Language>
     */
    public static function active(): Collection
    {
        try {
            return Language::activeCached();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return static::active()->pluck('code')->all();
    }

    public static function isSupported(string $code): bool
    {
        return in_array($code, static::codes(), true);
    }

    public static function language(?string $code = null): ?Language
    {
        return static::active()->firstWhere('code', $code ?? static::current());
    }

    public static function label(?string $code = null): string
    {
        $code ??= static::current();

        $language = static::language($code);

        return $language?->native_name ?: ($language?->name ?: strtoupper($code));
    }

    public static function direction(?string $code = null): string
    {
        return static::language($code)?->direction ?: 'ltr';
    }

    public static function isRtl(?string $code = null): bool
    {
        return static::direction($code) === 'rtl';
    }
}
