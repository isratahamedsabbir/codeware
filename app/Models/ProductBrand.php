<?php

namespace App\Models;

use App\Concerns\HasCreator;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
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

    /**
     * `slug` is a virtual accessor derived from the primary-locale name —
     * brands (unlike Products/ProductCategories) have no paired Page to own a
     * slug, so it's computed on the fly for the storefront's /brand/{slug}
     * links. Uses the same separator as Slug::make so it round-trips with the
     * FrontendController::resolveBrand() lookup.
     */
    protected $appends = ['slug'];

    protected function slug(): Attribute
    {
        return Attribute::make(
            get: fn () => Str::slug(
                (string) ($this->getTranslation('name', Locale::primary(), false)
                    ?: $this->getTranslation('name', 'en', false)),
                '-',
            ),
        );
    }

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
