<?php

namespace App\Models;

use App\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class ProductBrand extends Model
{
    use HasCreator, HasFactory, HasTranslations, SoftDeletes;

    // Lives in the unified taxonomy table alongside PostCategory /
    // ProductCategory / Tag — this type distinguishes brand rows.
    //
    // Brands are split into post/product pools exactly like tags, but with
    // their own type values (post_brand / product_brand): the tag global
    // scope matches plain 'post'/'product', so reusing those strings here
    // would make brand rows leak into every Tag query.
    protected $table = 'categories';

    public const TYPE_POST = 'post_brand';

    public const TYPE_PRODUCT = 'product_brand';

    public const TYPES = [self::TYPE_POST, self::TYPE_PRODUCT];

    public array $translatable = ['name'];

    protected $fillable = ['type', 'name', 'logo', 'status', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('type', function (Builder $builder) {
            $builder->whereIn('type', self::TYPES);
        });

        static::creating(function (ProductBrand $brand) {
            if (empty($brand->type)) {
                $brand->type = self::TYPE_PRODUCT;
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
