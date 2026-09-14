<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasFactory;

    protected $fillable = ['country_id', 'division_id', 'name', 'status'];

    protected static function booted(): void
    {
        static::saving(function (District $district) {
            if ($district->isDirty('division_id') || ! $district->country_id) {
                $district->country_id = Division::find($district->division_id)?->country_id;
            }
        });
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function upazilas(): HasMany
    {
        return $this->hasMany(Upazila::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
