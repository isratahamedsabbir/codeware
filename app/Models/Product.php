<?php

namespace App\Models;

use App\Concerns\CachesContent;
use App\Concerns\HasComments;
use App\Concerns\HasCreator;
use App\Concerns\HasFaqs;
use App\Concerns\HasReviews;
use App\Services\EmailTemplateService;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use CachesContent, HasComments, HasCreator, HasFactory, HasFaqs, HasReviews, HasTranslations, SoftDeletes;

    protected static function booted(): void
    {
        static::created(function (Product $product) {
            if (! Setting::get('notify_subscribers_on_new_product')) {
                return;
            }

            $name = is_array($product->name)
                ? ($product->name[Locale::primary()] ?? reset($product->name))
                : $product->name;

            $variables = [
                'product_name' => $name,
                'product_url' => rtrim(config('app.frontend_url'), '/').'/products/'.$product->slug,
                'site_name' => Setting::get('site_name'),
            ];

            $emailTemplateService = app(EmailTemplateService::class);

            Subscriber::subscribed()->pluck('email')->each(
                fn (string $email) => $emailTemplateService->send('new_product_notification', $email, $variables)
            );
        });
    }

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name', 'description',
        'brand_id', 'vendor_id', 'created_by', 'sku', 'variations',
        'featured_image', 'status', 'product_type', 'price', 'discount_price', 'quantity', 'charge_shipping', 'is_featured',
        'is_upcoming', 'sort_order', 'warranty_months',
    ];

    protected $casts = [
        'variations' => 'array',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'quantity' => 'integer',
        'charge_shipping' => 'boolean',
        'is_featured' => 'boolean',
        'is_upcoming' => 'boolean',
        'sort_order' => 'integer',
        'warranty_months' => 'integer',
    ];

    /**
     * `slug` is a virtual accessor (see below), not a real column — Eloquent
     * only includes accessor-only attributes in toArray()/JSON output when
     * they're appended here, otherwise dumping the whole model silently drops
     * it (e.g. Admin API's `'categories' => $p->categories`).
     */
    protected $appends = ['slug'];

    protected function slug(): Attribute
    {
        return Attribute::make(get: fn () => $this->page?->slug);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'category_product', 'product_id', 'category_id');
    }

    /**
     * Shares the single polymorphic `taggables` pivot table with Post's own
     * tags() — see Tag::posts()/Tag::products().
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(ProductBrand::class, 'brand_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(ProductVendor::class, 'vendor_id');
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

    /**
     * Who has favorited this product (see App\Support\Favorites).
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * A discount price only counts if it's actually cheaper than the regular
     * price — guards against a stale/mistaken discount_price left equal to or
     * above price still showing a "sale" badge.
     */
    public function hasDiscount(): bool
    {
        return $this->discount_price !== null && (float) $this->discount_price < (float) $this->price;
    }

    /**
     * A blank/null quantity means out of stock, same as an explicit 0.
     */
    public function inStock(): bool
    {
        return (int) $this->quantity > 0;
    }

    public function isDigital(): bool
    {
        return $this->product_type === 'digital';
    }

    public function hasWarranty(): bool
    {
        return $this->warranty_months !== null && $this->warranty_months > 0;
    }

    /**
     * The combination rows the admin left switched on ('visible').
     *
     * @return array<int, array<string, mixed>>
     */
    public function visibleVariations(): array
    {
        return collect($this->variations ?? [])
            ->filter(fn (array $row) => $row['visible'] ?? true)
            ->values()
            ->all();
    }

    public function hasVisibleVariations(): bool
    {
        return $this->visibleVariations() !== [];
    }

    /**
     * The visible variation row matching exactly the given attribute map
     * (attribute name => value, e.g. ['Color' => 'Red', 'Size' => 'M']) — or
     * null when no such combination exists (or it's been toggled off). Used by
     * the cart, checkout and order pipeline so a selected option always
     * resolves against today's catalog.
     *
     * @param  array<string, string>  $attributes
     * @return array<string, mixed>|null
     */
    public function variationRow(array $attributes): ?array
    {
        ksort($attributes);

        foreach ($this->visibleVariations() as $row) {
            $rowAttributes = $row['attributes'] ?? [];
            ksort($rowAttributes);

            if ($rowAttributes === $attributes) {
                return $row;
            }
        }

        return null;
    }

    /**
     * The price a selected option actually sells at — the combination's own
     * price when one is set, else the base product price.
     *
     * @param  array<string, string>  $attributes
     */
    public function variationPrice(array $attributes): float
    {
        $row = $this->variationRow($attributes);

        if ($row && ($row['price'] ?? null) !== null) {
            return (float) $row['price'];
        }

        return (float) $this->price;
    }

    /**
     * The discounted price for a selected option, when cheaper than the
     * combination's own price — same guard as hasDiscount().
     *
     * @param  array<string, string>  $attributes
     */
    public function variationDiscount(array $attributes): ?float
    {
        $row = $this->variationRow($attributes);
        $unitPrice = $this->variationPrice($attributes);

        if ($row && ($row['discount_price'] ?? null) !== null && (float) $row['discount_price'] < $unitPrice) {
            return (float) $row['discount_price'];
        }

        return null;
    }

    /**
     * Whether a selected option can be ordered — the combination's own
     * quantity when it overrides stock tracking, else the base product's
     * stock. A null combination quantity means "inherit the base product",
     * same convention as the public API detail response.
     *
     * @param  array<string, string>  $attributes
     */
    public function variationInStock(array $attributes): bool
    {
        $row = $this->variationRow($attributes);

        if ($row) {
            return ($row['quantity'] ?? null) === null ? $this->inStock() : (int) $row['quantity'] > 0;
        }

        return $this->inStock();
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

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('is_upcoming', true);
    }

    public function scopeDigital(Builder $query): Builder
    {
        return $query->where('product_type', 'digital');
    }

    public function scopePhysical(Builder $query): Builder
    {
        return $query->where('product_type', 'physical');
    }
}
