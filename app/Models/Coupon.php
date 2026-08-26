<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
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
