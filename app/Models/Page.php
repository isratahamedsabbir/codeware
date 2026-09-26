<?php

namespace App\Models;

use App\Concerns\CachesContent;
use App\Concerns\HasCreator;
use App\Support\Locale;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use CachesContent, HasCreator, HasFactory, HasTranslations;

    /**
     * The SEO copy is per-locale alongside the content it describes, so a
     * translated page can carry a translated meta title and description instead
     * of advertising itself in English on every locale. og_image / twitter_image
     * / canonical_base / canonical_slug are deliberately absent: a URL has one
     * spelling, and translating it would only produce hreflang pointing nowhere.
     */
    public array $translatable = [
        'title', 'content', 'description',
        'seo_title', 'seo_description', 'og_title', 'og_description',
        'twitter_title', 'twitter_description',
    ];

    protected $fillable = [
        'user_id', 'title', 'slug', 'content', 'description', 'puck_data', 'status',
        'template', 'type', 'product_id', 'post_id', 'category_id', 'sort_order', 'seo_title', 'seo_description',
        'og_image', 'og_title', 'og_description',
        'twitter_title', 'twitter_description', 'twitter_image', 'no_index', 'no_follow',
        'canonical_base', 'canonical_slug', 'constant',
    ];

    protected $casts = [
        'puck_data' => 'array',
        'constant' => 'array',
        'no_index' => 'boolean',
        'no_follow' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            if (empty($page->slug)) {
                $title = is_array($page->title)
                    ? ($page->title[Locale::primary()] ?? reset($page->title))
                    : $page->title;
                $page->slug = Slug::make($title);
            } else {
                $page->slug = Slug::lower($page->slug);
            }

            $page->collapseEmptySeoTranslations();
        });

        static::updating(function (Page $page) {
            if ($page->isDirty('content') && $page->getOriginal('content') && auth()->id()) {
                PageRevision::create([
                    'page_id' => $page->id,
                    'user_id' => auth()->id(),
                    'content' => $page->getOriginal('content'),
                ]);
            }
        });
    }

    /**
     * Turns a translatable SEO attribute that is empty in every locale back into
     * a real NULL.
     *
     * spatie/laravel-translatable writes a cleared field as `[]` or
     * `{"en":null}` — json that is present but says nothing. That is a different
     * thing from NULL to `whereNull()`, to a `$page->seo_title` null check, and
     * to an admin screen asking whether this page was ever given a meta title.
     * These columns were plain strings until SEO became translatable, and the
     * rest of the application (and its tests) still expects the old shape for
     * "unset", so the storage normalisation belongs here rather than in each of
     * the four admin forms that write these fields.
     */
    protected function collapseEmptySeoTranslations(): void
    {
        foreach (['seo_title', 'seo_description', 'og_title', 'og_description', 'twitter_title', 'twitter_description'] as $field) {
            $raw = $this->attributes[$field] ?? null;

            if (! is_string($raw) || $raw === '') {
                continue;
            }

            $decoded = json_decode($raw, true);

            if (! is_array($decoded) || array_filter($decoded, 'filled') === []) {
                $this->attributes[$field] = null;
            }
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->latest();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Constant is stored as a list of {key, value} pairs (so the admin form can
     * repeat/reorder/remove them like cards), but consumers want a
     * plain lookup map — this collapses it to key => value, skipping blank keys.
     *
     * @return array<string, string>
     */
    public function constantMap(): array
    {
        return collect($this->constant ?? [])
            ->filter(fn ($pair) => filled($pair['key'] ?? null))
            ->pluck('value', 'key')
            ->all();
    }
}
