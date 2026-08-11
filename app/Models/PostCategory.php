<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class PostCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'categories';

    public array $translatable = ['name', 'description', 'seo_title', 'seo_description'];

    protected $fillable = ['type', 'name', 'slug', 'description', 'sort_order', 'seo_title', 'seo_description', 'status'];

    protected static function booted(): void
    {
        static::addGlobalScope('type', function (Builder $builder) {
            $builder->where('type', 'post');
        });

        static::creating(function (PostCategory $category) {
            $category->type = 'post';
        });

        static::saving(function (PostCategory $category) {
            if (empty($category->slug)) {
                $name = is_array($category->name)
                    ? ($category->name['en'] ?? reset($category->name))
                    : $category->name;
                $category->slug = Str::slug($name);
            }
        });
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }
}
