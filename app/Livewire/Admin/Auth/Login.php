<?php

namespace App\Livewire\Admin\Auth;

use App\Models\User;
use App\Rules\Recaptcha;
use App\Support\Mfa;
use App\Support\Recaptcha as RecaptchaSupport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
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

        // The account is looked up before the captcha is judged because "does this role
        // want a reCAPTCHA" is a question about the account, not the form. Still
        // ahead of the password check, and the same generic auth.failed comes
        // out either way, so asking first costs nothing in enumeration.
        $user = User::where(Fortify::username(), $this->email)->first();

        $this->ensureRecaptchaPasses($user);
        $this->ensureIsNotRateLimited();

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

        // A second factor is owed when this account's role says so and it has one to
        // answer with, per the MFA switch on the role (Admin → Roles).
        // Mfa::mustChallenge() is also what App\Http\Middleware\EnsureMfaEnforced
        // asks on the far side, so the login and the gate behind it agree by
        // construction.
        //
        // A user with *no* factor is a different case and is not handled here:
        // there is nothing to challenge them with, so it is the enforcement
        // middleware that sends them to enrol (admin.mfa.required).
        if (Mfa::mustChallenge($user)) {
            // Never log them in yet — park the half-authenticated attempt in
            // the session the way Fortify does and let the (host-only)
            // two-factor challenge finish the job on this same host.
            session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $this->remember,
            ]);

            // The challenge route is domainless, so it is built on this panel's
            // configured host — the login.id/login.remember session it has to
            // finish only exists there, and a bounce to APP_URL would strand the
            // sign-in. See Mfa::challengeUrl().
            $this->redirect(Mfa::challengeUrl(Mfa::AUDIENCE_ADMIN), navigate: false);

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
     * Whether this account's role asked for a reCAPTCHA — see
     * App\Support\Recaptcha. Null (an address with no account behind it) is not
     * asked to answer one: the password below is about to fail anyway, and a
     * captcha error there would leak which addresses exist.
     *
     * @throws ValidationException
     */
    private function ensureRecaptchaPasses(?User $user): void
    {
        if (! RecaptchaSupport::requiredFor($user)) {
            return;
        }

        $this->validate([
            'recaptchaToken' => ['required', new Recaptcha],
        ]);
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
