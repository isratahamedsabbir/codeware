<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    public const PAYMENT_STATUSES = ['pending', 'paid', 'failed', 'refunded'];

    protected $fillable = [
        'order_number', 'customer_name', 'customer_email', 'customer_phone',
        'shipping_address', 'status', 'payment_method', 'payment_status',
        'currency', 'subtotal', 'total', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    /**
     * 'ORD-' + an 8-char code — collisions are astronomically unlikely, but the
     * column is uniquely constrained, so re-roll on the rare clash rather than
     * letting the insert fail.
     */
    private static function generateOrderNumber(): string
    {
        do {
            $number = 'ORD-'.strtoupper(Str::random(8));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
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
}
