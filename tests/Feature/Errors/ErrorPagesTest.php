<?php

use Illuminate\Support\Facades\Route;

/*
 * The branded error pages in resources/views/errors/ are rendered by the
 * framework's exception handler for any HttpException (404 from a missing
 * route, 403 from Gate::authorize, 419 from a stale CSRF token, 429 from the
 * throttle middleware, …). These lock in that they actually get used, and that
 * the shared shell stays self-contained.
 */
beforeEach(function () {
    // Otherwise the framework renders Ignition instead of the error views.
    config(['app.debug' => false]);

    Route::get('/__errors/{code}', fn (string $code) => abort((int) $code, '', [
        'Retry-After' => '120',
    ]));
});

it('renders the branded 404 page for a missing route', function () {
    $this->get('/this-route-does-not-exist')
        ->assertNotFound()
        ->assertSee('err-card', escape: false)
        ->assertSee('404')
        ->assertSee('Page not found')
        ->assertSee('We could not find the page you are looking for')
        // Generic, not tied to the shop.
        ->assertDontSee('Browse shop')
        ->assertSee('noindex, nofollow', escape: false);
});

it('renders the branded 401 page', function () {
    $this->get('/__errors/401')
        ->assertStatus(401)
        ->assertSee('err-card', escape: false)
        ->assertSee('401')
        ->assertSee('Unauthorized')
        ->assertSee(route('login'))
        // 401 has no useful history to go back to.
        ->assertDontSee('onclick="history.back()"', escape: false);
});

it('renders the branded 403 page', function () {
    $this->get('/__errors/403')
        ->assertForbidden()
        ->assertSee('err-card', escape: false)
        ->assertSee('403')
        ->assertSee('Access denied')
        ->assertSee('permission');
});

it('renders the branded 419 page', function () {
    $this->get('/__errors/419')
        ->assertStatus(419)
        ->assertSee('err-card', escape: false)
        ->assertSee('419')
        ->assertSee('session has expired');
});

it('renders the branded 429 page with the Retry-After wait', function () {
    $this->get('/__errors/429')
        ->assertStatus(429)
        ->assertSee('err-card', escape: false)
        ->assertSee('429')
        ->assertSee('Too many requests')
        ->assertSee('120s');
});

it('renders the branded 500 page with a support reference', function () {
    $this->get('/__errors/500')
        ->assertStatus(500)
        ->assertSee('err-card', escape: false)
        ->assertSee('500')
        ->assertSee('Something went wrong')
        ->assertSee('Error reference');

    // The reference is derived from the request, not the exception, so it must
    // not leak the stack trace or the exception class.
    $this->get('/__errors/500')
        ->assertDontSee('Stack trace', escape: false)
        ->assertDontSee('vendor/laravel', escape: false);
});

it('renders the branded 503 page with the estimated downtime', function () {
    $this->get('/__errors/503')
        ->assertStatus(503)
        ->assertSee('err-card', escape: false)
        ->assertSee('503')
        ->assertSee('back shortly')
        ->assertSee('2m');
});

it('falls back to the catch-all 4xx page for unmapped client errors', function () {
    $this->get('/__errors/409')
        ->assertStatus(409)
        ->assertSee('err-card', escape: false)
        ->assertSee('409')
        ->assertSee('Request could not be completed');
});

it('falls back to the catch-all 5xx page for unmapped server errors', function () {
    $this->get('/__errors/502')
        ->assertStatus(502)
        ->assertSee('err-card', escape: false)
        ->assertSee('502')
        ->assertSee('Something went wrong')
        ->assertSee('Error reference');
});

it('keeps API responses as JSON instead of HTML error pages', function () {
    $this->getJson('/this-route-does-not-exist')
        ->assertNotFound()
        ->assertHeader('content-type', 'application/json');

    $this->getJson('/__errors/403')
        ->assertForbidden()
        ->assertHeader('content-type', 'application/json');
});

/*
 * A real crash (dead database, fatal error) is a plain Throwable, so without the
 * render callback in bootstrap/app.php the framework bypasses
 * resources/views/errors entirely and falls back to Symfony's generic page.
 */
it('renders the branded 500 page for a genuine crash, not just abort(500)', function () {
    Route::get('/__errors/boom', function () {
        throw new RuntimeException('the database is on fire');
    });

    $this->get('/__errors/boom')
        ->assertStatus(500)
        ->assertSee('err-card', escape: false)
        ->assertSee('500')
        ->assertSee('Something went wrong')
        ->assertSee('Error reference')
        // The page must never carry the exception itself.
        ->assertDontSee('the database is on fire')
        ->assertDontSee('RuntimeException');
});

it('keeps a genuine crash as JSON for API clients', function () {
    Route::get('/__errors/boom', fn () => throw new RuntimeException('the database is on fire'));

    $this->getJson('/__errors/boom')
        ->assertStatus(500)
        ->assertHeader('content-type', 'application/json');
});

it('leaves the stack trace alone in debug mode', function () {
    config(['app.debug' => true]);

    Route::get('/__errors/boom', function () {
        throw new RuntimeException('the database is on fire');
    });

    // Assembled at runtime on purpose. Ignition embeds the source of whatever
    // threw — this file — into its payload, so a needle written out as a literal
    // here would match that embedded source and assert nothing at all.
    $brandedShell = implode('-', ['err', 'shell']);

    $this->get('/__errors/boom')
        ->assertStatus(500)
        ->assertDontSee($brandedShell, escape: false);
});

/*
 * An error page that depends on the compiled asset manifest, the layout or the
 * database will itself fail to render on a fresh deploy or during an outage —
 * which is exactly when it is needed. It has to stay self-contained.
 */
it('keeps the error page shell self-contained', function () {
    $shell = file_get_contents(resource_path('views/components/errors/page.blade.php'));

    // Ignore the file's own docblock, which necessarily names these things.
    $markup = preg_replace('/\{\{--[\s\S]*?--\}\}/', '', $shell);

    foreach (['@vite', '@flux', '@livewire', '@extends', 'x-layouts', 'x-storefront'] as $dependency) {
        expect($markup)->not->toContain($dependency);
    }
});
