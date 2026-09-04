<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\AdminAlert;
use App\Providers\FortifyServiceProvider;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Fires exactly once per lockout — the 'login' rate limiter (see
 * FortifyServiceProvider) already increments its counter for this email+IP
 * before the credential check runs, so by the Nth (LOGIN_MAX_ATTEMPTS'th)
 * failure the counter reads exactly N; every attempt after that is blocked
 * by the limiter itself and never reaches here, so this can't spam admins
 * for the rest of the 5-minute window.
 */
class NotifyAdminOnRepeatedFailedLogin
{
    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        if (! $email || ! request()) {
            return;
        }

        // Illuminate\Routing\Middleware\ThrottleRequests hashes named-limiter keys as
        // md5($limiterName.$limit->key) before hitting the cache (ThrottleRequests::
        // $shouldHashKeys defaults to true) — replicate that exactly, or this reads
        // an entry the middleware never touched and never matches.
        $key = md5('login'.FortifyServiceProvider::loginThrottleKey(request()));

        if (RateLimiter::attempts($key) !== FortifyServiceProvider::LOGIN_MAX_ATTEMPTS) {
            return;
        }

        Notification::send(
            User::where('is_admin', true)->get(),
            new AdminAlert(
                'Repeated failed login attempts',
                sprintf(
                    '%d wrong password attempts for "%s" from %s — locked out for 5 minutes.',
                    FortifyServiceProvider::LOGIN_MAX_ATTEMPTS,
                    $email,
                    request()->ip(),
                ),
            ),
        );
    }
}
