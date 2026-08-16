<?php

namespace App\Models;

use App\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
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
        'user_id', 'category_id', 'title', 'slug', 'description',
        'content', 'puck_data', 'featured_image', 'status', 'published_at',
        'reading_time',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'puck_data' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            if (empty($post->slug)) {
                $title = is_array($post->title)
                    ? ($post->title['en'] ?? reset($post->title))
                    : $post->title;
                $post->slug = Slug::make($title);
            }
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
