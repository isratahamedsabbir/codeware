<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['title', 'content', 'description'];

    protected $fillable = [
        'user_id', 'title', 'slug', 'content', 'description', 'puck_data', 'status',
        'template', 'type', 'product_id', 'post_id', 'sort_order', 'seo_title', 'seo_description',
        'og_image', 'og_title', 'og_description', 'no_index', 'no_follow',
    ];

    protected $casts = [
        'puck_data' => 'array',
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
                $page->slug = Str::slug($title);
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
}
