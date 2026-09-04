<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\LogAdminActivity;
use App\Http\Middleware\RequireFeature;
use App\Http\Middleware\SetLocale;
use App\Support\UnauthorizedAccessNotifier;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware(['web', 'auth', 'admin', 'activity-log'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Applies the globally configured locale. Appended to `web` (not `api`) because
        // the public API resolves its locale from the ?locale= query parameter instead.
        $middleware->appendToGroup('web', SetLocale::class);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'activity-log' => LogAdminActivity::class,
            'locale' => SetLocale::class,
            'feature' => RequireFeature::class,
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
    })->create();
