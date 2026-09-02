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
        return Cache::store('redis')->tags(['settings'])->rememberForever("setting:{$key}", function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);

        // A whole-tag flush (not just this key) so derived caches — like the
        // public settings list — go stale too, without each needing its own
        // explicit bust wired into every write path.
        Cache::store('redis')->tags(['settings'])->flush();
    }

    /**
     * All settings exposed to the public API (is_public = true), as key => value.
     * Cached like an individual Setting::get() key — busted by any Setting::set()
     * call, since that flushes the whole 'settings' tag.
     *
     * @return array<string, mixed>
     */
    public static function publicMap(): array
    {
        return Cache::store('redis')->tags(['settings'])->rememberForever('settings:public', function () {
            return static::where('is_public', true)->get()->pluck('value', 'key')->all();
        });
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
