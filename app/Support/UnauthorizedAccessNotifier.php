<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AdminAlert;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * Alerts admins whenever a Gate/Policy check denies a request — covers both
 * $this->authorize()/can: middleware denials and AdminMiddleware's outer
 * access-admin gate (see there), since both now go through Gate::authorize()
 * and therefore both throw this same exception. Wired from bootstrap/app.php's
 * exception reporting hook via Exceptions::stopIgnoring() (Laravel silences
 * AuthorizationException by default) + Exceptions::report().
 */
class UnauthorizedAccessNotifier
{
    /**
     * One alert per user(or IP)+URL combination per window — a script hammering
     * the same forbidden endpoint shouldn't flood the admin bell with repeats.
     */
    private const DEBOUNCE_MINUTES = 5;

    public static function handle(AuthorizationException $e, Request $request): void
    {
        $identity = $request->user()?->email ?? $request->ip();
        $debounceKey = 'forbidden-alert:'.sha1($identity.'|'.$request->path());

        if (! Cache::add($debounceKey, true, now()->addMinutes(self::DEBOUNCE_MINUTES))) {
            return;
        }

        Notification::send(
            User::where('is_admin', true)->get(),
            new AdminAlert(
                'Forbidden access attempt',
                sprintf(
                    '%s tried to access "%s" (%s) without permission from %s.',
                    $request->user()?->email ?? 'A guest',
                    $request->path(),
                    $request->method(),
                    $request->ip(),
                ),
            ),
        );
    }
}
