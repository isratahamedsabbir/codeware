<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'success', 'failed', 'refunded'];

    protected $fillable = [
        'order_id', 'reference', 'payment_method', 'amount', 'currency', 'status', 'meta', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (empty($transaction->reference)) {
                $transaction->reference = static::generateReference();
            }
        });
    }

    private static function generateReference(): string
    {
        do {
            $reference = 'TXN-'.strtoupper(Str::random(10));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
