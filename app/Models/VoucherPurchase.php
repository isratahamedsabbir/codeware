<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * A single voucher that was actually bought. The unique `code` is the voucher
 * identity (mirrors Order::order_number / Transaction::reference); `value`,
 * `price_paid` and `currency` are snapshotted at purchase time so later edits
 * to the Voucher product never rewrite an already-issued voucher.
 */
class VoucherPurchase extends Model
{
    use HasFactory;

    public const STATUS_ISSUED = 'issued';

    public const STATUS_REDEEMED = 'redeemed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUSES = [self::STATUS_ISSUED, self::STATUS_REDEEMED, self::STATUS_EXPIRED];

    protected $fillable = [
        'voucher_id', 'code', 'customer_name', 'customer_email', 'customer_phone',
        'recipient_name', 'message', 'price_paid', 'value', 'currency',
        'status', 'expires_at', 'purchased_at',
    ];

    /**
     * Mirrors the `status` column default so a freshly created (not re-fetched)
     * model already reports itself as issued.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_ISSUED,
    ];

    protected function casts(): array
    {
        return [
            'price_paid' => 'decimal:2',
            'value' => 'decimal:2',
            'expires_at' => 'datetime',
            'purchased_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VoucherPurchase $purchase) {
            if (empty($purchase->code)) {
                $purchase->code = static::generateCode();
            }

            $purchase->purchased_at ??= now();

            // The voucher product's validity window starts at purchase time —
            // e.g. a "valid for 90 days" gift voucher. Null valid_days means
            // the issued voucher never expires.
            if ($purchase->expires_at === null && $purchase->voucher) {
                $days = $purchase->voucher->valid_days;

                if ($days) {
                    $purchase->expires_at = $purchase->purchased_at->copy()->addDays($days);
                }
            }
        });
    }

    private static function generateCode(): string
    {
        do {
            $code = 'VCH-'.strtoupper(Str::random(10));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    /**
     * withTrashed() so a purchase still resolves the name of a voucher product
     * that has since been soft-deleted.
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class)->withTrashed();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function voucherName(): string
    {
        return $this->voucher?->getTranslation('name', 'en', false) ?: 'Voucher';
    }
}
