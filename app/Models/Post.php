<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Post extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['title', 'description', 'content'];

    protected $fillable = [
        'user_id', 'category_id', 'title', 'description',
        'content', 'featured_image', 'status', 'published_at',
        'reading_time',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * `slug` is a virtual accessor (see below), not a real column — Eloquent
     * only includes accessor-only attributes in toArray()/JSON output when
     * they're appended here, otherwise dumping the whole model silently drops
     * it (e.g. Admin API's `'category' => $post->category`).
     */
    protected $appends = ['slug'];

    protected function slug(): Attribute
    {
        return Attribute::make(get: fn () => $this->page?->slug);
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            if ($post->content) {
                $contentStr = is_array($post->content) ? json_encode($post->content) : $post->content;
                $wordCount = str_word_count(strip_tags($contentStr));
                $post->reading_time = max(1, (int) ceil($wordCount / 200));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function page(): HasOne
    {
        return $this->hasOne(Page::class, 'post_id')->where('type', 'post');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }
}
