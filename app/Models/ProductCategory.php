<?php

namespace App\Models;

use App\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Translatable\HasTranslations;

class ProductCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'categories';

    public array $translatable = ['name', 'description'];

    protected $fillable = ['type', 'name', 'slug', 'description', 'icon', 'sort_order', 'status'];

    protected static function booted(): void
    {
        static::addGlobalScope('type', function (Builder $builder) {
            $builder->where('type', 'product');
        });

        static::creating(function (ProductCategory $category) {
            $category->type = 'product';
        });

        static::saving(function (ProductCategory $category) {
            if (empty($category->slug)) {
                $name = is_array($category->name)
                    ? ($category->name['en'] ?? reset($category->name))
                    : $category->name;
                $category->slug = Slug::make($name);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function page(): HasOne
    {
        return $this->hasOne(Page::class, 'category_id')->where('type', 'product_category');
    }
}
