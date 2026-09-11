<?php

namespace App\Models;

use App\Services\EmailTemplateService;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'product_category_id', 'name', 'description',
        'faq', 'variations',
        'featured_image', 'status', 'price', 'discount_price', 'quantity', 'charge_shipping', 'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'faq' => 'array',
        'variations' => 'array',
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'quantity' => 'integer',
        'charge_shipping' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * `slug` is a virtual accessor (see below), not a real column — Eloquent
     * only includes accessor-only attributes in toArray()/JSON output when
     * they're appended here, otherwise dumping the whole model silently drops
     * it (e.g. Admin API's `'category' => $p->category`).
     */
    protected $appends = ['slug'];

    protected function slug(): Attribute
    {
        return Attribute::make(get: fn () => $this->page?->slug);
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
     * A null quantity means stock isn't tracked for this product — always
     * considered in stock. Otherwise in stock only while quantity is positive.
     */
    public function inStock(): bool
    {
        return $this->quantity === null || $this->quantity > 0;
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
