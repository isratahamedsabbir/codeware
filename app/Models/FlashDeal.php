<?php

namespace App\Models;

use App\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A time-boxed sale on a hand-picked set of products. Unlike a Discount, which is
 * stamped onto the product's own discount_price when the product is saved, a flash
 * deal is never written to the product: the price is worked out from the live
 * window every time it is read (see Product::flashPrice()), so the sale simply
 * stops when ends_at passes, with nothing to clean up.
 */
class FlashDeal extends Model
{
    use HasCreator, HasFactory;

    public const TYPE_PERCENTAGE = 'percentage';

    public const TYPE_FIXED = 'fixed';

    public const TYPES = [self::TYPE_PERCENTAGE, self::TYPE_FIXED];

    protected $fillable = [
        'name',
        'type',
        'value',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /** Switched on and inside its window right now. */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now());
    }

    public function isLive(): bool
    {
        return $this->status === 'active'
            && $this->starts_at->isPast()
            && $this->ends_at->isFuture();
    }

    /**
     * The deal price for a regular price, or null when the deal would not
     * actually make it cheaper.
     */
    public function priceFor(float $regularPrice): ?float
    {
        $off = $this->type === self::TYPE_PERCENTAGE
            ? $regularPrice * ((float) $this->value / 100)
            : (float) $this->value;

        $price = round(max($regularPrice - $off, 0), 2);

        return $price < $regularPrice ? $price : null;
    }

    /** "20% off" / "৳500 off" */
    public function valueLabel(): string
    {
        return $this->type === self::TYPE_PERCENTAGE
            ? rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'% off'
            : format_money((float) $this->value).' off';
    }
}
