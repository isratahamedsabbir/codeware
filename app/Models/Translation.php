<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Translation extends Model
{
    use HasFactory;

    protected $fillable = ['group', 'key', 'locale', 'value'];

    protected static function booted(): void
    {
        static::saved(fn (self $translation) => static::flushCache($translation->locale));
        static::deleted(fn (self $translation) => static::flushCache($translation->locale));
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'code');
    }

    public function scopeTranslated(Builder $query): Builder
    {
        return $query->whereNotNull('value')->where('value', '!=', '');
    }

    public function scopeUntranslated(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('value')->orWhere('value', ''));
    }

    public static function cacheKey(string $locale, string $group): string
    {
        return "translations:{$locale}:{$group}";
    }

    /**
     * Forget the loader cache for one locale (all groups) or, with no locale, every locale.
     */
    public static function flushCache(?string $locale = null): void
    {
        $locales = $locale
            ? [$locale]
            : static::query()->distinct()->pluck('locale')->all();

        $groups = static::query()->distinct()->pluck('group')->all();

        foreach ($locales as $code) {
            foreach ($groups as $group) {
                Cache::forget(static::cacheKey($code, $group));
            }
            // '*' may not be present in $groups yet on a fresh install.
            Cache::forget(static::cacheKey($code, '*'));
        }
    }
}
