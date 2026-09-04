<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class CmsSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cms';

    protected $fillable = [
        'page_id',
        'name',
        'sort_order',
        'cards',
        'content',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'cards' => 'array',
            'content' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * The public CMS API (Api\V1\CmsController) caches its responses under the
     * app's default cache store (CACHE_STORE in .env) — bumped here on every
     * write so create/update/delete/status changes show up immediately instead
     * of waiting out the cache lifetime. Bumping the version orphans every key
     * built from the old one, standing in for cache tagging (which only
     * redis/memcached/array support) so this works on any store.
     */
    public static function flushCache(): void
    {
        Cache::forever('cms:cache-version', self::cacheVersion() + 1);
    }

    private static function cacheVersion(): int
    {
        return (int) Cache::rememberForever('cms:cache-version', fn () => 1);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** @return array<int, array{image: ?string, title: ?string, description: ?string}> */
    public function localizedCards(): array
    {
        return collect($this->cards ?? [])->map(fn ($card) => [
            'image' => $card['image'] ?? null,
            'title' => $card['title'] ?? null,
            'description' => $card['description'] ?? null,
        ])->all();
    }

    /**
     * Content is stored as a list of {key, value} pairs (so the admin form can
     * repeat/reorder/remove them like cards), but consumers want a
     * plain lookup map — this collapses it to key => value, skipping blank keys.
     *
     * @return array<string, string>
     */
    public function contentMap(): array
    {
        return collect($this->content ?? [])
            ->filter(fn ($pair) => filled($pair['key'] ?? null))
            ->pluck('value', 'key')
            ->all();
    }

    /**
     * Named `scopeOfPage`, not `scopeForPage` — Eloquent's query builder already
     * has a real `forPage($page, $perPage)` pagination helper, and Eloquent's
     * __call() checks named scopes before falling through to it, so a scope
     * literally named `forPage` silently hijacks every paginate() call.
     */
    public function scopeOfPage(Builder $query, int $pageId): Builder
    {
        return $query->where('page_id', $pageId);
    }

    /**
     * A page's active CMS sections, cached in Redis (tag 'cms', kept forever)
     * — the same cache the public CMS API (Api\V1\CmsController) uses, so
     * flushCache() invalidates both on every write. Caches the *raw* attribute
     * form (getAttributes(), not toArray()) and re-hydrates into real models
     * on read, so callers still get full CmsSection instances with casts and
     * methods like localizedCards()/contentMap() intact — never cache
     * Eloquent objects/collections directly, and never toArray(): that
     * decodes the 'cards'/'content' JSON casts into plain arrays, which then
     * blow up when hydrate() re-applies the same cast on read (double-decoding
     * a PHP array instead of the JSON string it expects).
     *
     * @return Collection<int, CmsSection>
     */
    public static function cachedForPage(int $pageId): Collection
    {
        $rows = Cache::rememberForever(
            'cms:v'.self::cacheVersion().":page:{$pageId}:sections",
            fn () => static::active()->ofPage($pageId)->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (CmsSection $section) => $section->getAttributes())->all(),
        );

        return static::hydrate($rows);
    }
}
