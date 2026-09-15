<?php

namespace App\Support;

use App\Models\Setting;
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
    public static function token(User $user, string $name): string
    {
        $user->tokens()->where('name', $name)->delete();

        return $user->createToken($name, ['*'], now()->addMinutes(Setting::puckSessionMinutes()))->plainTextToken;
    }
}
