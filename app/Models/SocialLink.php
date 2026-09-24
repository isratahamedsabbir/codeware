<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SocialLink extends Model
{
    use HasFactory;

    protected $fillable = ['platform', 'label', 'url', 'sort_order'];

    /**
     * Request-scoped memo of the cached URL map — the footer partials call
     * urlsCached() several times a page, each read a SELECT on a database
     * cache store. Keyed by the cache repository instance so the memo dies
     * with the bootstrap that owns it (per PHP-FPM request, per app instance
     * in tests).
     */
    private static array $urls = [];

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * All social links ordered for display, platform => url (filled links only).
     *
     * Cached as a plain array (not an Eloquent collection) and rebuilt into a
     * Collection on every read — busted on any save/delete via the booted()
     * hooks above. See Language::activeCached() for why: caching a
     * Collection/model object directly through the database cache driver is
     * unreliable cross-process and intermittently unserializes as a broken
     * `__PHP_Incomplete_Class`.
     *
     * @return Collection<string, string>
     */
    public static function urlsCached(): Collection
    {
        $key = spl_object_id(Cache::getFacadeRoot());

        if (! array_key_exists($key, self::$urls)) {
            self::$urls[$key] = Cache::rememberForever(
                'social-links:all',
                fn () => static::query()->orderBy('sort_order')->get(['platform', 'url'])->toArray(),
            );
        }

        return collect(self::$urls[$key])->pluck('url', 'platform')->filter();
    }

    public static function url(string $platform): ?string
    {
        return static::urlsCached()->get($platform);
    }

    /**
     * Forget the cached URL map (also drops the request-scoped memo) — called
     * from the model's save/delete hooks and from code paths that write rows
     * through the query builder (admin Social Links screen), which skip those
     * events.
     */
    public static function flushCache(): void
    {
        self::$urls = [];
        Cache::forget('social-links:all');
    }
}
