<?php

namespace App\Providers;

use App\Models\MediaLibrary;
use App\Models\User;
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
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

        // Two admin tiers: Admin ('admin' role, every permission) and Staff
        // ('staff' role, content-only — see RolePermissionSeeder). access-admin
        // is the outer gate: it only decides who gets into /admin/* at all.
        // access-admin-system is the inner gate that further restricts the
        // system-level screens (Settings, Users, Roles/Permissions, Menu,
        // Activity History, Localization, Contacts) to Admin — Staff passes
        // the outer gate but not this one.
        // hasInactiveRole() also cuts off an already-open session the moment
        // its role is deactivated (Admin → Roles), not just fresh logins —
        // the login-time checks in FortifyServiceProvider and
        // Vendor\Auth\Login give a clearer message at the login form itself,
        // but a session opened before the role was deactivated would
        // otherwise keep working until it logged out on its own.
        Gate::define('access-admin', fn ($user) => ($user->hasRole('admin') || $user->hasRole('staff'))
            && ! $user->hasInactiveRole());

        Gate::define('access-admin-system', fn ($user) => $user->hasRole('admin') && ! $user->hasInactiveRole());

        // Vendor portal (App\Livewire\Vendor\*) — a separate, unrelated door from
        // access-admin above: a vendor-assigned user is never admin/staff,
        // and the portal deliberately doesn't reuse any admin route/gate, so it
        // can't accidentally inherit access to the rest of /admin/*. Requires both
        // the 'vendor' role AND at least one assigned vendor — the role alone (with
        // no vendor assigned yet) or a vendor assignment left over without the role
        // (see Users\Form::save(), which clears vendor_ids when the role is removed)
        // should never be enough on its own.
        Gate::define('access-vendor-portal', fn ($user) => $user->hasRole('vendor')
            && $user->vendors()->exists()
            && ! $user->hasInactiveRole());

        // File Manager reads/writes anywhere under the project root (including .env),
        // so — unlike most admin screens — it gets its own granular gates rather than
        // riding solely on the blanket access-admin check: 'view' for browsing/downloading,
        // 'manage' for anything that creates, edits, or deletes. 'manage' implies 'view' —
        // someone allowed to change files can always see them too. Deliberately does NOT
        // fall back to hasRole('admin') the way access-admin does — that would make the
        // permission unrevokable for admin-role users, defeating the point of having it.
        // The admin role holds every permission (RolePermissionSeeder), including these
        // two, so it still reaches File Manager by default — just revocably.
        Gate::define('manage-file-manager', function ($user) {
            try {
                return $user->hasPermissionTo('manage file manager');
            } catch (PermissionDoesNotExist) {
                // The permission hasn't been seeded yet (e.g. a fresh install) — fail
                // closed rather than crashing the request.
                return false;
            }
        });

        Gate::define('view-file-manager', function ($user) {
            if (Gate::forUser($user)->allows('manage-file-manager')) {
                return true;
            }

            try {
                return $user->hasPermissionTo('view file manager');
            } catch (PermissionDoesNotExist) {
                return false;
            }
        });

        Gate::policy(MediaLibrary::class, MediaLibraryPolicy::class);

        // Fortify::redirects('login') is a getter (config('fortify.home') is the
        // actual default, currently '/dashboard' — see config/fortify.php), not a
        // registrable hook, so post-login routing lives in routes/web.php's
        // /dashboard route instead of here. The vendor portal has its own
        // separate login on its own host (App\Livewire\Vendor\Auth\Login) and
        // was never reachable through this one anyway.

        $this->configureCustomerAuthNotificationUrls();
        $this->configureCreatorTracking();
    }

    /**
     * Role and Permission (spatie/laravel-permission) are third-party models
     * this app doesn't own the class of, so App\Concerns\HasCreator (used by
     * Product, ProductCategory, etc.) can't be applied to them directly —
     * this reproduces the same behavior externally: a `creating` listener
     * sets created_by once, and resolveRelationUsing() adds a real, eager-
     * loadable `creator` relation without subclassing either model.
     */
    private function configureCreatorTracking(): void
    {
        foreach ([Role::class, Permission::class] as $model) {
            $model::creating(function ($record) {
                if ($record->created_by) {
                    return;
                }

                if (auth()->check()) {
                    $record->created_by = auth()->id();

                    return;
                }

                // Seeders/tinker run with no authenticated user — see
                // App\Concerns\HasCreator, which this mirrors. whereHas()
                // rather than the role() scope: this fires from a `creating`
                // hook on Role/Permission themselves, so the 'admin' role row
                // may not exist yet (RolePermissionSeeder creates permissions
                // before the role) — role() throws RoleDoesNotExist in that
                // case, whereHas() just finds nothing.
                $record->created_by = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->value('id');
            });

            $model::resolveRelationUsing('creator', fn ($record) => $record->belongsTo(User::class, 'created_by'));
        }
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
        // setting and display_timezone()). With no $format given, falls back to the
        // "date_format" setting (see display_date_format()) so API responses and any
        // other unformatted call sites all follow the same admin-configurable format.
        // Registered on both Carbon and CarbonImmutable since Date::use() above makes
        // Eloquent/now() produce CarbonImmutable, but some dates (e.g. from third-party
        // packages) may still be plain Carbon.
        $toDisplay = function (?string $format = null) {
            return $this->setTimezone(display_timezone())->format($format ?? display_date_format());
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
