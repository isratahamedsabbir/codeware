<?php

namespace App\Models;

use App\Concerns\HasUniqueCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    use HasFactory, HasUniqueCode;

    protected $fillable = ['name', 'image', 'url', 'clicks', 'valid_from', 'valid_until'];

    protected $casts = [
        'clicks' => 'integer',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    protected function uniqueCodePrefix(): string
    {
        return 'AD';
    }

    /**
     * Only ads currently inside their validity window — a null bound means
     * unlimited on that side, and both null means always-on.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()));
    }

    public function isActive(): bool
    {
        if ($this->valid_from !== null && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until !== null && $this->valid_until->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * The single banner shown beside the product details section: the first
     * active ad (earliest id wins). Null when nothing is currently running.
     */
    public static function displayAd(): ?self
    {
        return static::query()->active()->orderBy('id')->first();
    }
}
