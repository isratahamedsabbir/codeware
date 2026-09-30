<?php

namespace App\Models;

use App\Concerns\CachesContent;
use App\Concerns\HasCreator;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class ProductBrand extends Model
{
    use CachesContent, HasCreator, HasFactory, HasTranslations, SoftDeletes;

    // Lives in the unified taxonomy table alongside PostCategory /
    // ProductCategory / Tag — the `kind` column sets this apart from those rows.
    //
    // Unlike the two category models, a brand is NOT locked to one pool: the
    // admin Brand screen manages both and filters by `type_id`, while the
    // pickers that need a single pool (the Product and Vendor product forms)
    // narrow with Type::subquery() themselves. So there is no TYPE constant here
    // — which is also what tells App\Support\Taxonomy not to add a pool
    // constraint to a rule built from this model.
    protected $table = 'categories';

    public const KIND = Category::KIND_BRAND;

    public array $translatable = ['name'];

    protected $fillable = ['kind', 'type_id', 'name', 'logo', 'status', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * `slug` is a virtual accessor derived from the primary-locale name —
     * brands (unlike Products/ProductCategories) have no paired Page to own a
     * slug, so it's computed on the fly for the storefront's /brand/{slug}
     * links. Uses the same separator as Slug::make so it round-trips with the
     * App\Http\Controllers\Themes\ThemeController::resolveBrand() lookup.
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
        static::addGlobalScope('kind', function (Builder $builder) {
            $builder->where('kind', self::KIND);
        });

        static::creating(function (ProductBrand $brand) {
            $brand->kind = self::KIND;

            // See Tag::creating() — a brand's pool is a choice rather than a
            // property of the model, and type_id is NOT NULL, so an explicit
            // create() with no pool falls back to Product.
            $brand->type_id ??= Type::idFor(Type::PRODUCT);
        });
    }

    /**
     * The content pool this brand belongs to — which is what decides whether it
     * appears in the Product form's brand dropdown or the Post form's.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id');
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
