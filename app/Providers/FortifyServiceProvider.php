<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\RedirectIfTwoFactorAuthenticatable;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Recaptcha as RecaptchaSupport;
use App\Support\Themes;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RedirectsIfTwoFactorAuthenticatable as RedirectsIfTwoFactorAuthenticatableContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Failed login attempts (per email+IP) allowed before the 5-minute lockout
     * kicks in — shared with Listeners\NotifyAdminOnRepeatedFailedLogin, which
     * fires the admin alert at exactly this count.
     */
    public const LOGIN_MAX_ATTEMPTS = 3;

    /**
     * Requests one address+IP pair may make within OTP_QUOTA_HOURS.
     *
     * Deliberately looser than OtpService::MAX_ISSUES_PER_HOUR, and the two are
     * not trying to be the same limit. This one is the cheap edge: it turns a
     * script away cheaply, but it is keyed on the pair, so an attacker with a
     * pool of addresses walks straight past it, and every request it lets
     * through still costs the mailer something. The quota is the one that
     * actually bounds mail, because it counts against the address and not the
     * caller.
     *
     * Set above the quota on purpose: a customer who fumbles their address a
     * few times should meet the flow's own "you have asked for several codes
     * already" message, which tells them what happened, rather than a bare 429
     * from the edge of the application telling them nothing.
     */
    public const OTP_MAX_REQUESTS = 10;

    /** The window OTP_MAX_REQUESTS is counted over. */
    public const OTP_QUOTA_HOURS = OtpService::QUOTA_HOURS;

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerThemeAwareRedirects();
    }

    /**
     * Where a successful login or registration lands. On an ecommerce store
     * that's the customer's own account page; everywhere else it's the
     * existing behavior (config('fortify.home'), i.e. /dashboard → the admin
     * panel). Applied with extend() rather than bind()/singleton(): Fortify's
     * own provider posts its response bindings after ours, which would have
     * clobbered a plain rebind — extenders run at resolution time instead, so
     * the redirect follows whichever theme is active on each request.
     */
    private function registerThemeAwareRedirects(): void
    {
        $this->app->extend(LoginResponseContract::class, function () {
            return new class implements LoginResponseContract
            {
                public function toResponse($request)
                {
                    return $request->wantsJson()
                        ? response()->json(['two_factor' => false])
                        : redirect()->intended(FortifyServiceProvider::accountPath());
                }
            };
        });

        $this->app->extend(RegisterResponseContract::class, function () {
            return new class implements RegisterResponseContract
            {
                public function toResponse($request)
                {
                    return $request->wantsJson()
                        ? new JsonResponse('', 201)
                        : redirect()->intended(FortifyServiceProvider::accountPath());
                }
            };
        });
    }

    /**
     * The post-login/post-register landing path — the customer account when an
     * ecommerce storefront is active, otherwise the app's normal home (the
     * non-ecommerce themes keep their current /dashboard → admin behavior).
     */
    public static function accountPath(): string
    {
        return Themes::active() === 'ecommerce'
            ? route('account.dashboard')
            : config('fortify.home');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();
        $this->configureAuthentication();
        $this->configureTwoFactorChallenge();
        $this->configurePasskeys();
    }

    /**
     * Swaps Fortify's "is a second factor enabled?" step for the one that asks
     * App\Support\Mfa instead of the two_factor_* columns.
     *
     * Bound as a singleton rather than resolved per request because it holds the
     * guard and the login rate limiter — exactly as Fortify's own binding does —
     * and because the pipeline names the contract, not the class.
     */
    private function configureTwoFactorChallenge(): void
    {
        $this->app->singleton(
            RedirectsIfTwoFactorAuthenticatableContract::class,
            RedirectIfTwoFactorAuthenticatable::class
        );
    }

    /**
     * Gate on what a verified passkey is allowed to log into.
     *
     * Without this a passkey is a standalone door that walks straight past
     * everything the password door checks: the WebAuthn signature proves you
     * hold the key, but nothing about the account behind it. So a passkey
     * belonging to a blocked account, or to one whose role has been deactivated
     * since it was registered, would still sign in — where the password path
     * would have refused it.
     *
     * Reusing the same two checks and the same messages the password path uses
     * (configureAuthentication above) rather than inventing a third set of
     * rules: the answer to "may this account sign in" should not depend on which
     * credential it arrived with.
     */
    private function configurePasskeys(): void
    {
        Passkeys::authorizeLoginUsing(function (Request $request, PasskeyUser $user, Passkey $passkey): bool {
            if ($user->is_blocked) {
                return false;
            }

            if ($user->hasInactiveRole()) {
                return false;
            }

            return true;
        });
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Replaces Fortify's default credential check with the same email+password
     * lookup, plus a reCAPTCHA verification in front of it. Only enforced when
     * the account's role has reCAPTCHA switched on (Admin → Roles) and a secret
     * key is set (Settings → Env → reCAPTCHA) — otherwise this behaves exactly
     * like Fortify's default.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where(Fortify::username(), $request->{Fortify::username()})->first();

            // Asked after the lookup and before the password check, for the same
            // reason as the admin login: the requirement belongs to the role, and
            // a captcha error thrown at an address with no account behind it
            // would be a way of finding out which addresses are real.
            if (RecaptchaSupport::requiredFor($user)) {
                $request->validate([
                    'g-recaptcha-response' => ['required', RecaptchaSupport::rule()],
                ]);
            }

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return null;
            }

            // A blocked account (Admin → Users) is locked out immediately,
            // even with the correct password.
            if ($user->is_blocked) {
                $this->rejectLogin('Your account has been blocked.');
            }

            // A deactivated role (Admin → Roles) locks the account out
            // immediately, even with the correct password — see
            // User::hasInactiveRole().
            if ($user->hasInactiveRole()) {
                $this->rejectLogin('Your account access has been disabled.');
            }

            return $user;
        });
    }

    /**
     * Flashes the same message to session('error') — read by
     * layouts/auth/split.blade.php's toastr script on the page this
     * ValidationException redirects back to — in addition to the inline
     * field error Blade already renders from the errors bag, so a
     * blocked/deactivated account gets a toast, not just fine print under
     * the email field.
     *
     * @throws ValidationException
     */
    private function rejectLogin(string $message): never
    {
        session()->flash('error', $message);

        throw ValidationException::withMessages([
            Fortify::username() => $message,
        ]);
    }

    /**
     * Configure Fortify views.
     *
     * The guest pages a visitor signs in or recovers an account through are the
     * *active theme's* pages, resolved by template name the same way every other
     * storefront page is — so /login is the ecommerce login on an ecommerce site,
     * and on a theme that ships no auth/ folder it is a 404 rather than some other
     * design's page. It used to be the reverse: an "ecommerce or fall back to the
     * shared admin-styled page" switch, which meant a portfolio site answered /login
     * with a page badged "Admin Panel" (resources/views/pages/auth/login.blade.php,
     * from before the admin panel moved to its own host with its own login).
     *
     * So a theme opts into having customers by shipping these templates:
     *
     *     themes/{slug}/auth/login.blade.php
     *     themes/{slug}/auth/register.blade.php
     *     themes/{slug}/auth/forgot-password.blade.php
     *     themes/{slug}/auth/verify-code.blade.php      (the OTP step, by code)
     *     themes/{slug}/auth/reset-password.blade.php
     *
     * and into letting a signed-in customer do anything else by shipping the
     * account/ templates Themes::ROUTE_TEMPLATES lists. ecommerce ships all of them;
     * portfolio and default ship none, so on those a customer account simply does not
     * exist — and none of its links are rendered either.
     *
     * Deliberately still shared, because they are steps *inside* a flow that is
     * already themed rather than pages a visitor navigates to on their own, and a
     * theme cannot be expected to design them: the email-verification notice, the
     * two-factor challenge and the re-enter-your-password confirmation. A theme that
     * wants its own styling wraps or replaces them; nothing here 404s a signed-in
     * customer halfway through signing in.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view(Themes::viewOrFail('auth/login')));
        Fortify::registerView(fn () => view(Themes::viewOrFail('auth/register')));
        Fortify::resetPasswordView(fn () => view(Themes::viewOrFail('auth/reset-password')));
        Fortify::requestPasswordResetLinkView(fn () => view(Themes::viewOrFail('auth/forgot-password')));

        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Named 'passkeys' because that is what config/fortify.php's Features
        // options point the package's own routes at, and it is the only limiter
        // here that is not on a Fortify action — laravel/passkeys registers
        // those routes itself.
        //
        // Keyed on the credential's own id, not on the session's login.id,
        // because a registration is not a challenge: there is no half-authenticated
        // attempt to key off, the caller is simply signed in, and the ceremony
        // itself is the expensive part — every one of these hits a CPU-hard
        // attestation or assertion check. An account holding several devices
        // needs a few; a script does not.
        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            // 3 wrong-password attempts locks the email+IP pair out for 5 minutes.
            return Limit::perMinutes(5, self::LOGIN_MAX_ATTEMPTS)->by(self::loginThrottleKey($request));
        });

        RateLimiter::for('otp-request', function (Request $request) {
            // Stops the asking before it reaches the mailer at all. Keyed on the
            // address as well as the IP so that a shared connection — an office,
            // a mobile carrier putting hundreds of people behind one address —
            // does not lock out everybody else the moment a few of them try to
            // reset a password.
            //
            // A script is what this catches: it needs no waiting between
            // attempts, so it arrives here long before the per-address quota,
            // which has to be spaced out to be felt, would ever notice.
            return Limit::perHour(self::OTP_MAX_REQUESTS, self::OTP_QUOTA_HOURS)
                ->by(self::otpThrottleKey($request));
        });
    }

    /**
     * The email+IP signature the 'otp-request' rate limiter keys on.
     *
     * The address comes from the field on the request that asks for a code. The
     * redeem step carries no address of its own — it was established by the
     * earlier request and kept in the session — so where that is what is being
     * asked about, the session's copy is used, or else everyone redeeming a code
     * would collapse into one bucket under an empty key.
     */
    public static function otpThrottleKey(Request $request): string
    {
        $email = $request->input('email') ?: $request->session()->get(PasswordResetOtpController::PENDING_SESSION_KEY);

        return Str::transliterate(Str::lower((string) $email).'|'.$request->ip());
    }

    /**
     * The same email+IP signature the 'login' rate limiter keys on — pulled out
     * so Listeners\NotifyAdminOnRepeatedFailedLogin can read the same counter
     * (via RateLimiter::attempts()) instead of keeping its own.
     */
    public static function loginThrottleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());
    }
}
