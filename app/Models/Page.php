<?php

namespace App\Models;

use App\Concerns\CachesContent;
use App\Concerns\HasCreator;
use App\Support\CustomCode;
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
        'twitter_title', 'twitter_description', 'twitter_image', 'no_index', 'no_follow', 'custom_code_id',
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

        // Every save that changes the body — a Puck publish from the frontend,
        // a content edit, a restore — writes one revision holding the body as
        // it now stands, so the list is the page's publish history, newest
        // first. A save that leaves content and puck_data as they were (a
        // title or SEO tweak, a re-publish with no changes) writes nothing.
        static::saved(function (Page $page) {
            if (! $page->wasRecentlyCreated && ! $page->wasChanged(['content', 'puck_data'])) {
                return;
            }

            $content = $page->getAttributes()['content'] ?? null;
            $puck = $page->getAttributes()['puck_data'] ?? null;

            if (blank($content) && blank($puck)) {
                return;
            }

            $content = filled($content) ? $content : null;
            $puck = filled($puck) ? $puck : null;

            // wasRecentlyCreated stays true on a model instance that is saved
            // again after being created, so compare with the newest revision
            // rather than trusting it alone.
            $latest = PageRevision::where('page_id', $page->id)->latest('id')->first();

            if ($latest && $latest->content === $content && $latest->puck_data === $puck) {
                return;
            }

            PageRevision::create([
                'page_id' => $page->id,
                'user_id' => auth()->id(),
                'content' => $content,
                'puck_data' => $puck,
            ]);

            // Keep the history bounded: the newest PageRevision::KEEP per page.
            $stale = PageRevision::where('page_id', $page->id)
                ->orderByDesc('id')->skip(PageRevision::KEEP)->take(PHP_INT_MAX)->pluck('id');

            if ($stale->isNotEmpty()) {
                PageRevision::whereIn('id', $stale)->delete();
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

    /**
     * Writes a revision's content/layout back onto this page. The save goes
     * through the normal hook, so it lands in the history as a new revision on
     * top. Returns false when the revision holds nothing to write.
     */
    public function restoreRevision(PageRevision $revision): bool
    {
        $attributes = array_filter([
            'content' => $revision->contentData(),
            'puck_data' => $revision->puckDataArray(),
        ], fn ($value) => $value !== null);

        if ($attributes === []) {
            return false;
        }

        $this->update($attributes);

        return true;
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->latest()->latest('id');
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
     * The Settings → Custom Code snippet this page picked ({id, name, head,
     * body}), or null when it picked none or the snippet has since been
     * deleted. The global code is not repeated here — it is in the public
     * settings.
     *
     * @return array{id: string, name: string, head: string, body: string}|null
     */
    public function customCode(): ?array
    {
        return CustomCode::forPage($this);
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
