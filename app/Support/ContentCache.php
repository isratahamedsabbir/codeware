<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * A single version counter that every public-facing CMS cache key is built
 * from. Bumping it on any content write orphans all derived keys at once — a
 * stand-in for cache tagging that works on every store (which one is used is
 * driven entirely by CACHE_STORE in .env), the same pattern
 * Setting::set()/CmsSection::flushCache() use.
 */
class ContentCache
{
    public static function version(): int
    {
        return (int) Cache::rememberForever('content:cache-version', fn () => 1);
    }

    public static function bust(): void
    {
        Cache::forever('content:cache-version', self::version() + 1);
    }

    /**
     * Reads/writes a callback's result under a key namespaced by the current
     * content version, so the next bust() invisibly swaps every cached value.
     */
    public static function remember(string $key, Closure $callback, ?int $ttl = null): mixed
    {
        return Cache::remember('content:v'.self::version().":{$key}", $ttl, $callback);
    }
}
