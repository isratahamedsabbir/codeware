<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class ProductCategory extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name', 'description', 'seo_title', 'seo_description'];

    protected $fillable = ['name', 'slug', 'description', 'icon', 'sort_order', 'status', 'seo_title', 'seo_description', 'og_image'];

    protected static function booted(): void
    {
        static::saving(function (ProductCategory $category) {
            if (empty($category->slug)) {
                $name = is_array($category->name)
                    ? ($category->name['en'] ?? reset($category->name))
                    : $category->name;
                $category->slug = Str::slug($name);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
