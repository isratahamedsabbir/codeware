<?php

namespace App\Providers;

use App\Models\MediaLibrary;
use App\Policies\MediaLibraryPolicy;
use App\Support\DatabaseTranslationLoader;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerDatabaseTranslations();
    }

    /**
     * Let admin-managed `translations` rows override the lang/ files.
     *
     * This uses extend() rather than a fresh singleton binding because Laravel's
     * TranslationServiceProvider is deferred — it re-registers `translation.loader` the
     * first time the translator resolves, which would clobber a plain rebind. Extenders
     * survive that and run after the file loader is built.
     */
    protected function registerDatabaseTranslations(): void
    {
        $this->app->extend(
            'translation.loader',
            fn (Loader $loader) => new DatabaseTranslationLoader($loader),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        // Three admin tiers: Super Admin (is_admin=true, unconditional bypass everywhere),
        // Admin ('admin' role, every permission), and Staff ('staff' role, content-only —
        // see RolePermissionSeeder). access-admin is the outer gate: it only decides who
        // gets into /admin/* at all. access-admin-system is the inner gate that further
        // restricts the system-level screens (Settings, Users, Roles/Permissions, Menu,
        // Activity History, Localization, Contacts) to Admin/Super Admin — Staff passes
        // the outer gate but not this one.
        Gate::define('access-admin', fn ($user) => (bool) $user->is_admin
            || $user->hasRole('admin')
            || $user->hasRole('staff'));

        Gate::define('access-admin-system', fn ($user) => (bool) $user->is_admin || $user->hasRole('admin'));

        // File Manager reads/writes anywhere under the project root (including .env),
        // so — unlike most admin screens — it gets its own granular gates rather than
        // riding solely on the blanket access-admin check: 'view' for browsing/downloading,
        // 'manage' for anything that creates, edits, or deletes. 'manage' implies 'view' —
        // someone allowed to change files can always see them too. Deliberately does NOT
        // fall back to hasRole('admin') the way access-admin does — that would make the
        // permission unrevokable for admin-role users, defeating the point of having it.
        // is_admin still bypasses unconditionally, matching every other gate in the app.
        Gate::define('manage-file-manager', function ($user) {
            if ((bool) $user->is_admin) {
                return true;
            }

            try {
                return $user->hasPermissionTo('manage file manager');
            } catch (PermissionDoesNotExist) {
                // The permission hasn't been seeded yet (e.g. a fresh install) — fail
                // closed rather than crashing the request.
                return false;
            }
        });

        Gate::define('view-file-manager', function ($user) {
            if ((bool) $user->is_admin || Gate::forUser($user)->allows('manage-file-manager')) {
                return true;
            }

            try {
                return $user->hasPermissionTo('view file manager');
            } catch (PermissionDoesNotExist) {
                return false;
            }
        });

        Gate::policy(MediaLibrary::class, MediaLibraryPolicy::class);

        Fortify::redirects('login', fn () => route('admin.dashboard'));

        $this->configureCustomerAuthNotificationUrls();
    }

    /**
     * Customer accounts are API-only (see Api\V1\Auth\*), so verification/reset
     * emails must link back to an API endpoint (or the frontend) instead of
     * Fortify's session-based web routes, which an API client can't use.
     */
    protected function configureCustomerAuthNotificationUrls(): void
    {
        VerifyEmail::createUrlUsing(fn ($notifiable) => URL::temporarySignedRoute(
            'api.v1.auth.email.verify',
            now()->addMinutes(60),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())],
        ));

        ResetPassword::createUrlUsing(fn ($notifiable, string $token) => sprintf(
            '%s/reset-password?token=%s&email=%s',
            rtrim(config('app.frontend_url'), '/'),
            $token,
            urlencode($notifiable->getEmailForPasswordReset()),
        ));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Dates are stored/computed internally in UTC (config('app.timezone')) — this
        // formats one for display in the admin's configured timezone (see the "timezone"
        // setting and display_timezone()). Registered on both Carbon and CarbonImmutable
        // since Date::use() above makes Eloquent/now() produce CarbonImmutable, but some
        // dates (e.g. from third-party packages) may still be plain Carbon.
        $toDisplay = function (string $format = 'M d, Y g:i A') {
            return $this->setTimezone(display_timezone())->format($format);
        };

        Carbon::macro('toDisplay', $toDisplay);
        CarbonImmutable::macro('toDisplay', $toDisplay);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
