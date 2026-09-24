<?php

namespace App\Models;

use App\Concerns\HasUniqueCode;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'code', 'password', 'photo', 'signature', 'provider', 'provider_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUniqueCode, Notifiable, TwoFactorAuthenticatable;

    /**
     * Roles that can never be a delivery rider — only a plain customer account
     * can (see Users\Form and the 'access-delivery-portal' gate).
     */
    public const DELIVERY_INELIGIBLE_ROLES = ['admin', 'staff', 'vendor'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
            'is_delivery_boy' => 'boolean',
        ];
    }

    /**
     * The auto-generated USR-XXXXXXXX business code (see HasUniqueCode).
     */
    protected function uniqueCodePrefix(): string
    {
        return 'USR';
    }

    /**
     * Normalizes case on every write (registration, admin creation, social
     * login, profile updates) so "User@Example.com" and "user@example.com"
     * can't end up as two different accounts.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value !== null ? Str::lower($value) : $value,
        );
    }

    /**
     * Query the 1-on-1 chat conversations this user is a participant of, on either
     * side of the pair. Not a formal Eloquent relation since a conversation's other
     * participant can be in either the user_one_id or user_two_id column.
     */
    public function conversations(): Builder
    {
        return Conversation::forUser($this);
    }

    /**
     * Vendor(s) this user can access the vendor portal for (App\Livewire\Vendor\*)
     * — see 'access-vendor-portal' gate. A user can be assigned to more than one
     * vendor, in which case the portal shows products/orders across all of them.
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(ProductVendor::class, 'product_vendor_user', 'user_id', 'vendor_id');
    }

    /**
     * Orders assigned to this user as their delivery rider (see Order::deliveryBoy()).
     */
    public function assignedDeliveries(): HasMany
    {
        return $this->hasMany(Order::class, 'delivery_boy_id');
    }

    /**
     * True when this account is marked as a delivery rider and still holds
     * no admin/staff/vendor role — the flag alone isn't enough, so a rider
     * later promoted to one of those roles loses delivery access right away.
     */
    public function isDeliveryBoy(): bool
    {
        return $this->is_delivery_boy && ! $this->hasAnyRole(self::DELIVERY_INELIGIBLE_ROLES);
    }

    /**
     * Riders an admin can assign an order to — see isDeliveryBoy(), plus
     * blocked accounts left out since they can't log in to deliver.
     */
    public function scopeDeliveryBoys(Builder $query): Builder
    {
        return $query->where('is_delivery_boy', true)
            ->where('is_blocked', false)
            ->whereDoesntHave('roles', fn (Builder $q) => $q->whereIn('name', self::DELIVERY_INELIGIBLE_ROLES));
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocument::class);
    }

    /**
     * Favorite products this user saved (see App\Support\Favorites).
     */
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * FCM device tokens this user has registered (see FirebaseTokenController
     * — POST/DELETE /api/v1/firebase/tokens) — App\Support\Firebase::sendToUser()
     * pushes to every one of them.
     */
    public function firebaseTokens(): HasMany
    {
        return $this->hasMany(FirebaseToken::class);
    }

    /**
     * True when this user holds at least one role (Admin → Roles) that's
     * been switched to inactive — used to lock the account out of both
     * logins (FortifyServiceProvider, Vendor\Auth\Login) and the
     * access-admin/access-vendor-portal gates the moment a role is
     * deactivated, without waiting for them to log out on their own.
     */
    public function hasInactiveRole(): bool
    {
        return $this->roles()->where('status', 'inactive')->exists();
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Get the user's profile photo URL
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo) {
            return null;
        }

        return Storage::disk('public')->url($this->photo);
    }

    /**
     * Get the user's signature image URL
     */
    public function getSignatureUrlAttribute(): ?string
    {
        if (! $this->signature) {
            return null;
        }

        return Storage::disk('public')->url($this->signature);
    }
}
