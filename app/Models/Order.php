<?php

namespace App\Models;

use App\Concerns\HasUniqueCode;
use App\Services\OrderEmailService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory, HasUniqueCode;

    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    public const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    /**
     * The normal fulfillment progression, 'cancelled' excluded — used only to
     * compare positions for canBeCancelled() below, not as a display order.
     */
    public const FULFILLMENT_PROGRESSION = ['pending', 'processing', 'shipped', 'delivered'];

    protected $fillable = [
        'user_id', 'order_number', 'customer_name', 'customer_email', 'customer_phone',
        'shipping_address', 'status', 'payment_method', 'payment_status',
        'currency', 'subtotal', 'coupon_code', 'discount', 'vat_amount', 'vat_rate',
        'shipping_method', 'shipping_cost', 'total', 'notes',
        'delivery_boy_id', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // A confirmation to the customer and a notification to the admin —
        // both best-effort (see OrderEmailService), so a mail failure never
        // blocks the order itself from being created.
        static::created(function (Order $order) {
            $service = app(OrderEmailService::class);
            $service->sendCustomerConfirmation($order);
            $service->sendAdminNotification($order);
        });
    }

    /**
     * The auto-generated ORD-XXXXXXXX order number lives in the existing
     * `order_number` column, not a new `code` column — see HasUniqueCode.
     */
    protected function uniqueCodeColumn(): string
    {
        return 'order_number';
    }

    protected function uniqueCodePrefix(): string
    {
        return 'ORD';
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * The customer account this order belongs to, when placed by a signed-in
     * user — null for guest checkouts (see the public order API).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The delivery rider assigned from Admin → Orders → Show — see the
     * delivery portal (App\Livewire\Delivery\*), which only ever shows a
     * rider the orders assigned to them here.
     */
    public function deliveryBoy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_boy_id');
    }

    /**
     * Whether the assigned rider can still complete this order — not once
     * it's already delivered or has been cancelled.
     */
    public function isAwaitingDelivery(): bool
    {
        return ! in_array($this->status, ['delivered', 'cancelled'], true);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePaymentStatus(Builder $query, string $status): Builder
    {
        return $query->where('payment_status', $status);
    }

    public function scopePaymentMethod(Builder $query, string $method): Builder
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Every order this customer account can see — those explicitly attached to
     * the account (user_id) plus any order their email placed before signing
     * up (or through the guest API), so the account page never silently drops
     * pre-account history.
     */
    public function scopeForCustomer(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhere(fn (Builder $q2) => $q2->whereNull('user_id')->where('customer_email', $user->email));
        });
    }

    /**
     * True when this order belongs to the given customer account — either
     * attached directly or placed with their email (see scopeForCustomer).
     */
    public function belongsToCustomer(User $user): bool
    {
        return $this->user_id === $user->id
            || ($this->user_id === null && strcasecmp((string) $this->customer_email, (string) $user->email) === 0);
    }

    /**
     * False once this order has reached (or passed) the admin-configured
     * cutoff status — e.g. cutoff = 'shipped' blocks cancelling a shipped or
     * delivered order, but still allows it while pending/processing. See the
     * "Cancellation Rule" settings modal on the Orders admin screen.
     */
    public function canBeCancelled(): bool
    {
        if ($this->status === 'cancelled') {
            return false;
        }

        $currentIndex = array_search($this->status, self::FULFILLMENT_PROGRESSION, true);
        $cutoffIndex = array_search(Setting::orderCancellationCutoffStatus(), self::FULFILLMENT_PROGRESSION, true);

        if ($currentIndex === false || $cutoffIndex === false) {
            return true;
        }

        return $currentIndex < $cutoffIndex;
    }
}
