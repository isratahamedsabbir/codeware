<?php

namespace App\Support;

use App\Models\User;

/**
 * The user payload the customer API returns on its auth endpoints.
 *
 * Deliberately small and deliberately identical everywhere: an API client that
 * logs in with a password, registers, or finishes a two-factor challenge has to
 * be able to treat the three responses as the same object. It was three private
 * copies of this array before the MFA challenge needed a fourth — the risk with
 * a hand-copied contract is not that one copy is wrong, it is that they drift
 * apart and a client that learned one shape silently stops parsing the others.
 *
 * Deliberately *not* the admin API's user resource, which carries roles,
 * permissions and other panel-only fields that have no business in a token a
 * customer app is holding.
 */
final class ApiUser
{
    /**
     * @return array{id: int, name: string, email: string, email_verified_at: ?string, email_verified_at_display: ?string}
     */
    public static function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'email_verified_at_display' => $user->email_verified_at?->toDisplay(),
        ];
    }
}
