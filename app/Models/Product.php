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

class Product extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'product_category_id', 'name', 'slug', 'description',
        'faq',
        'featured_image', 'status', 'price', 'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'faq' => 'array',
        'price' => 'decimal:2',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if (empty($product->slug)) {
                $name = is_array($product->name)
                    ? ($product->name['en'] ?? reset($product->name))
                    : $product->name;
                $product->slug = Slug::make($name);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function gallery(): BelongsToMany
    {
        return $this->belongsToMany(MediaLibrary::class, 'product_media')
            ->withPivot('sort_order')
            ->orderBy('product_media.sort_order');
    }

    public function page(): HasOne
    {
        return $this->hasOne(Page::class, 'product_id')->where('type', 'product');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'inactive');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }
}
