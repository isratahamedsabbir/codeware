<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'group', 'is_public'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(self::cacheKey($key), function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        // Bumping the version orphans every key built from the old version —
        // including derived caches like the public settings list — without
        // needing cache tagging, which only redis/memcached/array support.
        // Which store is actually used is driven entirely by CACHE_STORE in .env.
        Cache::forever('settings:cache-version', self::cacheVersion() + 1);
    }

    /**
     * All settings exposed to the public API (is_public = true), as key => value.
     * Cached like an individual Setting::get() key — busted by any Setting::set()
     * call, since that bumps the shared cache version.
     *
     * @return array<string, mixed>
     */
    public static function publicMap(): array
    {
        return Cache::rememberForever(self::cacheKey('__public'), function () {
            return static::where('is_public', true)->get()->pluck('value', 'key')->all();
        });
    }

    private static function cacheKey(string $key): string
    {
        return 'setting:v'.self::cacheVersion().":{$key}";
    }

    private static function cacheVersion(): int
    {
        return (int) Cache::rememberForever('settings:cache-version', fn () => 1);
    }

    /**
     * Default row count for paginated admin list tables and the public API —
     * the single place every ->paginate() call reads from, driven by
     * Settings → General → Pagination.
     */
    public static function perPage(): int
    {
        return (int) static::get('pagination_per_page', 10);
    }
}
