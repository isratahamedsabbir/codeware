<?php

namespace App\Models;

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

#[Fillable(['name', 'email', 'password', 'photo', 'signature', 'provider', 'provider_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

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
        ];
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

    public function documents(): HasMany
    {
        return $this->hasMany(UserDocument::class);
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
