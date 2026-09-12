<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Models\User;
use App\Rules\Recaptcha;
use App\Support\Recaptcha as RecaptchaSupport;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
     * Register any application services.
     */
    public function register(): void
    {
        //
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

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            return null;
        });
    }

    /**
     * Configure Fortify views.
     */
    private function configureViews(): void
    {
        Fortify::loginView(fn () => view('pages::auth.login'));
        Fortify::verifyEmailView(fn () => view('pages::auth.verify-email'));
        Fortify::twoFactorChallengeView(fn () => view('pages::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('pages::auth.confirm-password'));
        Fortify::registerView(fn () => view('pages::auth.register'));
        Fortify::resetPasswordView(fn () => view('pages::auth.reset-password'));
        Fortify::requestPasswordResetLinkView(fn () => view('pages::auth.forgot-password'));
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
