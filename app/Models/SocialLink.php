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

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('social-links:all'));
        static::deleted(fn () => Cache::forget('social-links:all'));
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
        $rows = Cache::rememberForever(
            'social-links:all',
            fn () => static::query()->orderBy('sort_order')->get(['platform', 'url'])->toArray(),
        );

        return collect($rows)->pluck('url', 'platform')->filter();
    }

    public static function url(string $platform): ?string
    {
        return static::urlsCached()->get($platform);
    }
}
