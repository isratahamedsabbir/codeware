<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class CmsSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cms';

    protected $fillable = [
        'page',
        'section',
        'title',
        'description',
        'buttons',
        'cards',
        'image',
        'bg_image',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'buttons' => 'array',
            'cards' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /**
     * The public CMS API (Api\V1\CmsController) caches its responses in Redis,
     * tagged 'cms' — flushed here on every write so create/update/delete/status
     * changes show up immediately instead of waiting out the cache lifetime.
     */
    public static function flushCache(): void
    {
        Cache::store('redis')->tags(['cms'])->flush();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Named `scopeOfPage`, not `scopeForPage` — Eloquent's query builder already
     * has a real `forPage($page, $perPage)` pagination helper, and Eloquent's
     * __call() checks named scopes before falling through to it, so a scope
     * literally named `forPage` silently hijacks every paginate() call.
     */
    public function scopeOfPage(Builder $query, string $page): Builder
    {
        return $query->where('page', $page);
    }
}
