<?php

namespace App\Livewire\Delivery\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * The delivery portal's own login — deliberately not Fortify's shared one
 * (which lives on the admin host), so the portal is a fully separate app
 * rather than a gated area behind the admin's login page. Session cookies
 * are host-only (see .env's SESSION_DOMAIN), so this genuinely is a
 * different session from an admin login on the main host.
 */
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function mount(): void
    {
        if (! Auth::check()) {
            return;
        }

        if (Gate::allows('access-delivery-portal')) {
            $this->redirect($this->intendedUrl(), navigate: false);

            return;
        }

        // Authenticated on this host but not (or no longer) a valid delivery rider —
        // nothing useful they can do here, so drop the stale session instead
        // of leaving them stuck looking at a login form while "logged in".
        $this->logout();
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // A blocked account (Admin → Users) is locked out immediately, even
        // with the correct password.
        if (Auth::user()->is_blocked) {
            $this->logout();
            $this->rejectLogin('Your account has been blocked.');
        }

        // A deactivated role (Admin → Roles) locks the account out
        // immediately, even with the correct password — see
        // User::hasInactiveRole(). Checked ahead of the generic
        // access-delivery-portal gate below so a deactivated rider sees why,
        // rather than the generic "delivery staff only" message.
        if (Auth::user()->hasInactiveRole()) {
            $this->logout();
            $this->rejectLogin('Your account access has been disabled.');
        }

        if (! Gate::allows('access-delivery-portal')) {
            $this->logout();

            throw ValidationException::withMessages([
                'email' => 'This portal is for delivery staff only.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        session()->regenerate();

        $this->redirect($this->intendedUrl(), navigate: false);
    }

    private function logout(): void
    {
        Auth::guard('web')->logout();

        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Dispatches the same message as a toast (this action never triggers a
     * full page reload, so — unlike the Fortify admin login — a flashed
     * session message would never be read; see layouts/auth/split.blade.php's
     * `notify` listener) in addition to the inline field error the thrown
     * exception below produces.
     *
     * @throws ValidationException
     */
    private function rejectLogin(string $message): never
    {
        $this->dispatch('notify', message: $message, type: 'error');

        throw ValidationException::withMessages([
            'email' => $message,
        ]);
    }

    /**
     * Where the auth middleware's unauthenticated() handler stashed the URL
     * a guest was trying to reach before being bounced here, if any.
     */
    private function intendedUrl(): string
    {
        return session()->pull('url.intended', route('delivery.orders'));
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 3)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
                'minutes' => ceil(RateLimiter::availableIn($this->throttleKey()) / 60),
            ]),
        ]);
    }

    /**
     * A separate bucket from the admin login's ('login|email|ip', enforced
     * via the throttle:login middleware on Fortify's route) — this route
     * doesn't go through Fortify at all, so it keeps its own.
     */
    private function throttleKey(): string
    {
        return 'delivery-login|'.Str::transliterate(Str::lower($this->email)).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.delivery.auth.login')
            ->layout('layouts::auth', ['title' => 'Delivery Login']);
    }
}
