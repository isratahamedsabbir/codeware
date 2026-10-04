<?php

namespace App\Support;

use App\Models\User;

/**
 * Issues the short-lived Sanctum token the external Puck editor app
 * authenticates with when it calls back into the admin API to load/save a
 * page's puck_data (see Livewire\Admin\{Pages,Posts,Products}\{Index,Form}
 * ::openPuckEditor()).
 *
 * The token name is scoped per page ($name should include the page id) —
 * every call site used to share one literal 'puck-builder' name and delete
 * any existing token with that name before minting a new one, which meant
 * opening the editor for page B silently invalidated a still-open editor
 * tab for page A (same admin, different page), breaking that tab's next
 * save with a 401. Scoping by page keeps re-opening the *same* page's
 * editor idempotent (old token for that page replaced) without touching
 * any other page's still-active session.
 */
class PuckEditor
{
    /**
     * The ability carried by a Puck token.
     *
     * Narrower than '*' on purpose. Nothing enforces it today - the admin API
     * routes gate on `can:access-admin`, a Gate over the user's roles, so any
     * valid token gets as far as the gate. But abilities are the only part of
     * a token a future `abilities:puck` middleware could check, and a token
     * that says '*' makes that impossible to add later without breaking the
     * editor. Cheap now, so keep it narrow.
     */
    public const ABILITY = 'puck:edit';

    /**
     * Token lifetime, in minutes. The single place every token mint reads from.
     *
     * Sourced from PUCK_SESSION in .env (not the settings table, which used
     * to hold a duplicate `puck_session_minutes` row — one value, one home)
     * and edited from the Settings button on the admin Pages screen, which
     * writes the .env key and clears the config cache so this picks it up.
     */
    public static function sessionMinutes(): int
    {
        return (int) config('cms.puck_session_minutes', 30);
    }

    public static function token(User $user, string $name): string
    {
        $user->tokens()->where('name', $name)->delete();

        return $user->createToken($name, [static::ABILITY], now()->addMinutes(static::sessionMinutes()))->plainTextToken;
    }
}
