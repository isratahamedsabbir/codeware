<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'label', 'is_enabled'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('features:enabled-map'));
        static::deleted(fn () => Cache::forget('features:enabled-map'));
    }

    /**
     * key => is_enabled.
     *
     * Cached as a plain array (not an Eloquent collection) and rebuilt into a
     * Collection on every read — busted on any save/delete via the booted()
     * hooks above. See Language::activeCached() for why: caching a
     * Collection/model object directly through the database cache driver is
     * unreliable cross-process and intermittently unserializes as a broken
     * `__PHP_Incomplete_Class`.
     *
     * @return Collection<string, bool>
     */
    public static function enabledMapCached(): Collection
    {
        $rows = Cache::rememberForever(
            'features:enabled-map',
            fn () => static::query()->get(['key', 'is_enabled'])->toArray(),
        );

        return collect($rows)->pluck('is_enabled', 'key');
    }
}
