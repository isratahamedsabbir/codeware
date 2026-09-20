<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
use App\Rules\Recaptcha;
use App\Support\Recaptcha as RecaptchaSupport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Livewire\Component;

/**
 * The admin panel's own login — deliberately not Fortify's shared one (which
 * lives on the main host), so the panel is a fully separate app rather than a
 * gated area behind the public site's login. Session cookies are host-only
 * (see .env's SESSION_DOMAIN), so this genuinely is a different session from
 * a frontend login on the main host.
 */
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public string $recaptchaToken = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            return;
        }

        if (Gate::allows('access-admin')) {
            $this->redirect($this->intendedUrl(), navigate: false);

            return;
        }

        // Authenticated on this host but not (or no longer) a valid admin or
        // staff member — nothing useful they can do here, so drop the stale
        // session instead of leaving them stuck looking at a login form while
        // "logged in".
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

        $this->ensureRecaptchaPasses();
        $this->ensureIsNotRateLimited();

        $user = User::where(Fortify::username(), $this->email)->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // A blocked account (Admin → Users) is locked out immediately, even
        // with the correct password.
        if ($user->is_blocked) {
            $this->rejectLogin('Your account has been blocked.');
        }

        // A deactivated role (Admin → Roles) locks the account out
        // immediately, even with the correct password — see
        // User::hasInactiveRole(). Checked ahead of the generic access-admin
        // gate below so a deactivated admin sees why, rather than the generic
        // "not an admin" message.
        if ($user->hasInactiveRole()) {
            $this->rejectLogin('Your account access has been disabled.');
        }

        if (Gate::forUser($user)->denies('access-admin')) {
            throw ValidationException::withMessages([
                'email' => 'This portal is for admins and staff only.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        if ($this->hasTwoFactorEnabled($user)) {
            // Never log them in yet — park the half-authenticated attempt in
            // the session the way Fortify does and let the (host-only)
            // two-factor challenge finish the job on this same host.
            session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $this->remember,
            ]);

            // The challenge route is domainless, so generate its URL explicitly
            // on this panel's host — the remember/session there only exists on
            // the admin host, and a bounce to APP_URL would strand the login.
            $this->redirect(config('app.admin_url').'/two-factor-challenge', navigate: false);

            return;
        }

        Auth::login($user, $this->remember);

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
        return session()->pull('url.intended', route('admin.dashboard'));
    }

    /**
     * @throws ValidationException
     */
    private function ensureRecaptchaPasses(): void
    {
        if (! RecaptchaSupport::verificationRequired()) {
            return;
        }

        $this->validate([
            'recaptchaToken' => ['required', new Recaptcha],
        ]);
    }

    private function hasTwoFactorEnabled(User $user): bool
    {
        if (! in_array(TwoFactorAuthenticatable::class, class_uses_recursive($user), true)) {
            return false;
        }

        if (Fortify::confirmsTwoFactorAuthentication()) {
            return filled($user->two_factor_secret) && ! is_null($user->two_factor_confirmed_at);
        }

        return filled($user->two_factor_secret);
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
     * Deliberately separate from the main site's Fortify limiter ('login',
     * enforced via the throttle:login middleware) — the admin panel login
     * doesn't go through Fortify at all, so it keeps its own, exactly like the
     * vendor portal's. Admins already get pinged about brute-forcing on the
     * main site's login (Listeners\NotifyAdminOnRepeatedFailedLogin); this
     * keeps the panel's own attempts self-contained.
     */
    private function throttleKey(): string
    {
        return 'admin-login|'.Str::transliterate(Str::lower($this->email)).'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.admin.auth.login')
            ->layout('layouts::auth', ['title' => 'Admin Login']);
    }
}
