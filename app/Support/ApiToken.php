<?php

namespace App\Support;

use App\Models\User;

/**
 * Mints the API tokens handed out on login. Everyone's token expires (see
 * config/sanctum.php), and anyone who can reach the admin API gets a much
 * shorter life, since a leaked admin token is the worst case.
 */
class ApiToken
{
    public static function issue(User $user, string $name = 'customer-api'): string
    {
        $expires = $user->can('access-admin')
            ? now()->addMinutes((int) config('sanctum.admin_expiration', 480))
            : null; // falls back to the global sanctum.expiration

        return $user->createToken($name, ['*'], $expires)->plainTextToken;
    }
}
