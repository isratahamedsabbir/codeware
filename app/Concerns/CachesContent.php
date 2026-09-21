<?php

namespace App\Concerns;

use App\Support\ContentCache;

/**
 * Bumps the shared content-cache version whenever the model is created,
 * updated, or deleted — orphaning every public-facing cache key built from the
 * previous version. Wired into the content models, it keeps the frontend/API
 * caches drifting at most one write behind the admin, instead of waiting out a
 * cache lifetime.
 */
trait CachesContent
{
    protected static function bootCachesContent(): void
    {
        static::saved(fn () => ContentCache::bust());
        static::deleted(fn () => ContentCache::bust());
    }
}
