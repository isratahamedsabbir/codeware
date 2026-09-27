<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One product line on an order.
 *
 * There is no type discriminator: an order is always products. A service is
 * requested through a Booking (App\Models\Booking), never bought through the
 * cart, so there is no second kind of line for this table to hold.
 */
class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'item_name', 'sku', 'unit_price', 'quantity', 'line_total', 'variations',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'integer',
            'line_total' => 'decimal:2',
            'variations' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Null once the product is deleted — the line stays on the receipt with its
     * snapshotted name, sku and price, so the sale is still readable.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
