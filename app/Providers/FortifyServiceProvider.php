<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Http\Controllers\Auth\PasswordResetOtpController;
use App\Models\User;
use App\Rules\Recaptcha;
use App\Services\OtpService;
use App\Support\Recaptcha as RecaptchaSupport;
use App\Support\Themes;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;

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
     * lookup, plus a reCAPTCHA verification in front of it. Only enforced while
     * reCAPTCHA is switched on and a secret key is set (Settings → Env →
     * reCAPTCHA) — otherwise this behaves exactly like Fortify's default.
     */
    private function configureAuthentication(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            if (RecaptchaSupport::verificationRequired()) {
                $request->validate([
                    'g-recaptcha-response' => ['required', new Recaptcha],
                ]);
            }

            $user = User::where(Fortify::username(), $request->{Fortify::username()})->first();

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
     * Configure Fortify views. The shared auth pages (dark admin-style layout,
     * used by the default/portfolio themes) stay untouched; an ecommerce
     * storefront gets its own storefront-styled login/register/password pages
     * that match the theme's header/footer.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => self::themedView('frontend.themes.ecommerce.auth.login', 'pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::registerView(fn () => self::themedView('frontend.themes.ecommerce.auth.register', 'pages::auth.register'));
        Fortify::resetPasswordView(fn () => self::themedView('frontend.themes.ecommerce.auth.reset-password', 'pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => self::themedView('frontend.themes.ecommerce.auth.forgot-password', 'pages::auth.forgot-password'));
    }

    /**
     * Pick the ecommerce theme's view when the theme is active — otherwise fall
     * back to the shared (non-storefront) page, so portfolio/default themes
     * keep the exact pages they already render.
     *
     * Public and static so the password reset controllers (which replace
     * Fortify's own, because that flow is by code rather than by link — see
     * App\Http\Controllers\Auth\PasswordResetOtpController) render through the
     * exact same switch instead of keeping a second copy of it.
     */
    public static function themedView(string $ecommerceView, string $sharedView): View
    {
        return Themes::active() === 'ecommerce' ? view($ecommerceView) : view($sharedView);
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
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
