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

    private static function cacheKey(string $key): string
    {
        return 'setting:v'.self::cacheVersion().":{$key}";
    }

    public static function cacheVersion(): int
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

    /**
     * How long a minted Puck editor token (Sanctum PAT) stays valid — the
     * single place every openPuckEditor()/saveAndOpenPageBuilder() call
     * reads from, driven by the Settings button on the admin Pages screen.
     */
    public static function puckSessionMinutes(): int
    {
        return (int) static::get('puck_session_minutes', 30);
    }

    /**
     * The fulfillment status past which an order can no longer be cancelled —
     * see Order::canBeCancelled(). Driven by the "Cancellation Rule" settings
     * modal on the admin Orders screen.
     */
    public static function orderCancellationCutoffStatus(): string
    {
        return static::get('order_cancellation_cutoff_status', 'shipped');
    }

    /**
     * Stock level at or below which a product counts as "low stock" and gets
     * its warning row on the admin Products list — driven by the Stock
     * Settings modal on that screen.
     */
    public static function productMinStockQuantity(): int
    {
        return (int) static::get('product_min_stock_quantity', 10);
    }
}
