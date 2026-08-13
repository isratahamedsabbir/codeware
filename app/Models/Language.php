<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Language extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'direction',
        'flag',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Language rows drive both the locale list and the translation cache, so any
        // write has to bust the caches that were built from them.
        static::saved(fn (self $language) => $language->flushCaches());
        static::deleted(fn (self $language) => $language->flushCaches());
    }

    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class, 'locale', 'code');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Make this the one and only default language. A default language is always active.
     */
    public function makeDefault(): void
    {
        static::where('id', '!=', $this->id)->update(['is_default' => false]);

        $this->forceFill(['is_default' => true, 'is_active' => true])->save();
    }

    /**
     * How many of this language's keys have a non-empty value, as a 0-100 percentage.
     */
    public function completion(): int
    {
        $total = Translation::distinct()->count('key');

        if ($total === 0) {
            return 0;
        }

        $translated = Translation::where('locale', $this->code)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->count();

        return (int) round(min($translated, $total) / $total * 100);
    }

    public function flushCaches(): void
    {
        Cache::forget('languages:active');
        Translation::flushCache($this->code);
    }

    /**
     * Active languages, ordered — cached because the admin layout reads this every request.
     *
     * Cached as plain attribute arrays (not Eloquent models) and rehydrated on every read.
     * Caching model/collection objects directly is unreliable here: whether the database
     * cache driver's unserialize() call happens to run after this process has already
     * autoloaded Collection/Language is non-deterministic, so it intermittently comes back
     * as a broken `__PHP_Incomplete_Class` and gets silently swallowed by the try/catch in
     * Locale::active(), leaving the language switcher with zero options.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function activeCached()
    {
        $rows = Cache::rememberForever(
            'languages:active',
            fn () => static::query()->active()->ordered()->get()->toArray(),
        );

        return static::hydrate($rows);
    }
}
