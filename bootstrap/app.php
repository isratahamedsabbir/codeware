<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureUserIsNotBlocked;
use App\Http\Middleware\LogAdminActivity;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RequireFeature;
use App\Http\Middleware\ScopeSessionCookieToHost;
use App\Http\Middleware\SetLocale;
use App\Support\UnauthorizedAccessNotifier;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // Vendor portal — its own subdomain rather than a path prefix, with
            // its own login (App\Livewire\Vendor\Auth\Login) rather than
            // sharing Fortify's — so it's a fully separate panel from the admin
            // one, not just a gated area behind the same login. Only 'web' here:
            // routes/vendor.php applies 'auth' + 'can:access-vendor-portal'
            // itself to everything except its own /login route.
            // See config('app.vendor_host') for how the host is derived — the
            // two panels stay fully separate because each is bound to its own
            // host (unreachable on the other), not by any path-based guard.
            Route::middleware('web')
                ->domain(config('app.vendor_host'))
                ->name('vendor.')
                ->group(base_path('routes/vendor.php'));

            // Delivery-rider portal — same separate-host, own-login setup as
            // the vendor portal above. routes/delivery.php applies 'auth' +
            // 'can:access-delivery-portal' itself to everything except /login.
            Route::middleware('web')
                ->domain(config('app.delivery_host'))
                ->name('delivery.')
                ->group(base_path('routes/delivery.php'));

            // Admin panel — its own subdomain rather than a path prefix, with
            // its own login (App\Livewire\Admin\Auth\Login) rather than
            // sharing Fortify's — so it's a fully separate panel from the main
            // site, the way the vendor portal already is. Only 'web' here:
            // routes/admin.php applies 'auth' + 'admin' + 'activity-log' itself
            // to everything except its own /login route. See
            // config('app.admin_host') for how the host is derived;
            // routes/web.php bounces /admin and /dashboard onto this host.
            Route::middleware('web')
                ->domain(config('app.admin_host'))
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Per-host session cookie name — see the middleware for why host-only
        // cookies alone weren't enough. Prepended so it runs before StartSession.
        $middleware->prependToGroup('web', ScopeSessionCookieToHost::class);

        // Applies the globally configured locale. Appended to `web` (not `api`) because
        // the public API resolves its locale from the ?locale= query parameter instead.
        $middleware->appendToGroup('web', SetLocale::class);

        // Blocking a user (Admin → Users) or deactivating one of their roles
        // (Admin → Roles) must reach every host — admin, vendor portal,
        // delivery portal and the plain site — not just gated routes, and the
        // API too, where the bearer token in use is revoked.
        $middleware->appendToGroup('web', EnsureUserIsNotBlocked::class);
        $middleware->appendToGroup('api', EnsureUserIsNotBlocked::class.':sanctum');

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'activity-log' => LogAdminActivity::class,
            'locale' => SetLocale::class,
            'feature' => RequireFeature::class,
        ]);

        // Settings → Env can flip the public site into maintenance mode (see
        // Livewire\Admin\Settings\Index::enableMaintenanceMode()) — the admin panel
        // and login stay reachable regardless, so turning it on can never lock the
        // admin out of the one place that can turn it back off. `livewire*` must be
        // excepted too: every Livewire component action (including the button that
        // calls disableMaintenanceMode()) round-trips through Livewire's own AJAX
        // endpoint (livewire-<hash>/update), not the admin/* prefix on the main
        // host — without this, that endpoint itself gets blocked by this same
        // middleware once maintenance mode is on, and the toggle can never turn
        // itself back off from the UI.
        // The framework's path-based exception list can't cover the admin panel any
        // more (it moved to its own host, see the routing block above), so the
        // global PreventRequestsDuringMaintenance is replaced with a host-aware
        // subclass that lets the admin host straight through.
        $middleware->replace(
            Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            PreventRequestsDuringMaintenance::class,
        );

        $middleware->preventRequestsDuringMaintenance(except: [
            'admin/*',
            'livewire*',
            'login',
            'logout',
            'two-factor-challenge',
        ]);
    })
    ->withEvents(discover: [
        __DIR__.'/../app/Listeners',
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        // AuthorizationException is silenced by Laravel's default $internalDontReport
        // list (403s are "expected"), so stopIgnoring() it first or report() below
        // would simply never run.
        $exceptions->stopIgnoring(AuthorizationException::class);

        $exceptions->report(function (AuthorizationException $e) {
            if ($request = request()) {
                UnauthorizedAccessNotifier::handle($e, $request);
            }

            // Still don't want these cluttering logs — just wanted the alert above.
            return false;
        });

        // A genuine crash — a dead database, a fatal error, a third-party SDK
        // blowing up — is a plain Throwable, not an HttpException, so the
        // framework never looks in resources/views/errors and falls back to
        // Symfony's generic page. Render the branded 500 instead, which is the
        // whole point of having one: the exact moment it is needed is usually
        // the moment something is broken.
        $exceptions->render(function (Throwable $e, $request) {
            // Everything with a status code of its own already resolves to
            // resources/views/errors/{code}.blade.php, and these two are turned
            // into redirects / JSON responses further down the stack.
            if ($e instanceof HttpExceptionInterface
                || $e instanceof ValidationException
                || $e instanceof AuthenticationException) {
                return null;
            }

            // Keep the stack trace for developers, keep JSON for API clients.
            if (config('app.debug') || $request->expectsJson() || $request->wantsJson()) {
                return null;
            }

            return response()->view('errors::500', [
                'errors' => new ViewErrorBag,
                'exception' => $e,
            ], 500);
        });
    })->create();
