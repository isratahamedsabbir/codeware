<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Maps a Google/Facebook identity onto a `users` row — shared by the storefront's
 * session login (Auth\SocialLoginController) and the API's token login
 * (Api\V1\Auth\SocialAuthController), so both link accounts the same way.
 */
class SocialAccount
{
    public const PROVIDERS = ['google', 'facebook'];

    public static function configured(string $provider): bool
    {
        return in_array($provider, self::PROVIDERS, true)
            && filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }

    public static function findOrCreate(string $provider, SocialiteUser $socialUser): User
    {
        $user = User::where('provider', $provider)->where('provider_id', $socialUser->getId())->first();

        if ($user) {
            return $user;
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            $user->forceFill([
                'provider' => $user->provider ?? $provider,
                'provider_id' => $user->provider_id ?? $socialUser->getId(),
            ])->save();

            return $user;
        }

        return User::create([
            'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'User',
            'email' => $socialUser->getEmail(),
            'password' => Str::random(40),
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'email_verified_at' => now(),
        ]);
    }
}
