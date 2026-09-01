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
        Cache::store('redis')->tags(['settings'])->forget("setting:{$key}");
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
