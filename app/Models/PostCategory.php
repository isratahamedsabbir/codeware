<?php

namespace App\Models;

use App\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Translatable\HasTranslations;

class PostCategory extends Model
{
    use HasCreator, HasFactory, HasTranslations;

    protected $table = 'categories';

    public const KIND = Category::KIND_CATEGORY;

    /**
     * This model is the post pool's categories and nothing else, so every query
     * it makes is locked to that pool — see the global scope below. The one place
     * that needs the id (its own creating() default) resolves it through
     * Type::idFor().
     */
    public const TYPE = Type::POST;

    public array $translatable = ['name', 'description'];

    protected $fillable = ['kind', 'type_id', 'name', 'description', 'sort_order', 'status'];

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
        static::addGlobalScope('kind', function (Builder $builder) {
            $builder->where('kind', self::KIND);
        });

        static::addGlobalScope('type', function (Builder $builder) {
            $builder->whereIn('type_id', Type::subquery(self::TYPE));
        });

        static::creating(function (PostCategory $category) {
            $category->kind = self::KIND;
            $category->type_id ??= Type::idFor(self::TYPE);
        });
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    public function page(): HasOne
    {
        return $this->hasOne(Page::class, 'category_id')->where('type', 'post_category');
    }
}
