<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'logo', 'signature', 'mobile', 'email', 'address', 'status', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'vendor_id');
    }

    /**
     * Users who can log into the vendor portal (App\Livewire\Vendor\*) and see
     * this vendor's products/orders — a user can be assigned to more than one
     * vendor, see User::vendors().
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'product_vendor_user', 'vendor_id', 'user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class, 'vendor_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
