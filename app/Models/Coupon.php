<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Coupon extends Model
{
    use HasFactory;

    public const TYPES = ['percentage', 'fixed'];

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_uses',
        'used_count',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon) {
            if ($coupon->code) {
                $coupon->code = strtoupper($coupon->code);
            }
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * A coupon with no linked products applies site-wide (the historical/default
     * behavior); linking products at all switches it to an allow-list.
     */
    public function isRestrictedToProducts(): bool
    {
        return $this->relationLoaded('products')
            ? $this->products->isNotEmpty()
            : $this->products()->exists();
    }

    public function appliesToProduct(int $productId): bool
    {
        return ! $this->isRestrictedToProducts()
            || ($this->relationLoaded('products')
                ? $this->products->contains('id', $productId)
                : $this->products()->whereKey($productId)->exists());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    /**
     * Whether this coupon can currently be applied to an order of the given amount.
     */
    public function isValidFor(float $orderAmount): bool
    {
        return $this->status === 'active'
            && ! $this->isExpired()
            && ! $this->isExhausted()
            && $orderAmount >= (float) ($this->min_order_amount ?? 0);
    }

    /**
     * The discount amount for an order of the given amount — never more than the
     * order itself, so a fixed-amount coupon can't produce a negative total.
     */
    public function discountFor(float $orderAmount): float
    {
        $discount = $this->type === 'percentage'
            ? $orderAmount * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min($discount, $orderAmount), 2);
    }
}
