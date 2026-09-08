<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function scopeSubscribed(Builder $query): Builder
    {
        return $query->where('status', 'subscribed');
    }

    public function scopeUnsubscribed(Builder $query): Builder
    {
        return $query->where('status', 'unsubscribed');
    }
}
