<?php

namespace App\Models;

use App\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['title', 'content', 'description'];

    protected $fillable = [
        'user_id', 'title', 'slug', 'content', 'description', 'puck_data', 'status',
        'template', 'type', 'product_id', 'post_id', 'category_id', 'sort_order', 'seo_title', 'seo_description',
        'og_image', 'og_title', 'og_description', 'no_index', 'no_follow',
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
                    ? ($page->title['en'] ?? reset($page->title))
                    : $page->title;
                $page->slug = Slug::make($title);
            } else {
                $page->slug = Slug::lower($page->slug);
            }
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
