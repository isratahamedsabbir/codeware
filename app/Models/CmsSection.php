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
     * Resolves a {en, bn} field to a plain string for the current app locale,
     * falling back to English when the current locale is empty.
     */
    public function localized(string $field): ?string
    {
        $value = $this->{$field};

        if (! is_array($value)) {
            return $value;
        }

        $locale = app()->getLocale();

        return $value[$locale] ?: ($value['en'] ?? null);
    }

    /** @return array<int, array{label: ?string, color: ?string, link: ?string}> */
    public function localizedButtons(): array
    {
        $locale = app()->getLocale();

        return collect($this->buttons ?? [])->map(fn ($button) => [
            'label' => $this->localizeArrayField($button['label'] ?? null, $locale),
            'color' => $button['color'] ?? null,
            'link' => $button['link'] ?? null,
        ])->all();
    }

    /** @return array<int, array{image: ?string, title: ?string, description: ?string}> */
    public function localizedCards(): array
    {
        $locale = app()->getLocale();

        return collect($this->cards ?? [])->map(fn ($card) => [
            'image' => $card['image'] ?? null,
            'title' => $this->localizeArrayField($card['title'] ?? null, $locale),
            'description' => $this->localizeArrayField($card['description'] ?? null, $locale),
        ])->all();
    }

    private function localizeArrayField(mixed $value, string $locale): ?string
    {
        if (! is_array($value)) {
            return $value;
        }

        return $value[$locale] ?: ($value['en'] ?? null);
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
