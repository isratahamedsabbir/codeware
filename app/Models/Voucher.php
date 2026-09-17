<?php

namespace App\Models;

use App\Concerns\HasCreator;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * An admin-defined gift voucher product ("Gift Voucher ৳500"). Customers buy
 * one through the public API, which issues a VoucherPurchase with a unique
 * code, and the buyer is emailed a PDF voucher — see VoucherEmailService.
 */
class Voucher extends Model
{
    use HasCreator, HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name', 'description', 'slug', 'price', 'value', 'currency',
        'valid_days', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'value' => 'decimal:2',
            'valid_days' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Voucher $voucher) {
            if (empty($voucher->slug)) {
                $name = is_array($voucher->name)
                    ? ($voucher->name[Locale::primary()] ?? reset($voucher->name))
                    : $voucher->name;
                $voucher->slug = Str::slug($name);
            }
        });
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(VoucherPurchase::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * How much the buyer saves versus the voucher's face value — 0 when sold
     * at (or above) face value. Purely informational for the storefront.
     */
    public function savings(): float
    {
        return round(max((float) $this->value - (float) $this->price, 0), 2);
    }
}
