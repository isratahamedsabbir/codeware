<?php

namespace App\Models;

use App\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Discount extends Model
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

    /**
     * Whether the discount is turned on and currently inside its validity window
     * (if one is set). Product assignment does not count here — an unassigned
     * discount is simply one nothing is attached to yet.
     */
    public function isCurrentlyValid(): bool
    {
        return $this->status === 'active'
            && ($this->starts_at === null || $this->starts_at->isPast())
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * The discounted price for a product of the given regular price, or the
     * original price untouched if this discount doesn't actually lower it.
     */
    public function priceFor(float $regularPrice): float
    {
        $discount = $this->type === self::TYPE_PERCENTAGE
            ? $regularPrice * ((float) $this->value / 100)
            : (float) $this->value;

        $price = $regularPrice - $discount;

        return round(max($price, 0), 2);
    }
}
