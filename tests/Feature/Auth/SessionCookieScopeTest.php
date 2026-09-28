<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

/**
 * Every host — the storefront, the admin panel, the vendor portal, the delivery
 * portal — gets its own session cookie name, so a cookie left over from
 * another host (or scoped to the parent domain) can never be read in place of
 * it. See App\Http\Middleware\ScopeSessionCookieToHost.
 *
 * The name has to be settled before *anything* reads the session store, and on
 * Fortify's own routes that is earlier than the middleware itself: Fortify's
 * service provider resolves the 'web' guard while registering, and Laravel
 * builds a route's controller middleware before the first middleware runs. So
 * /login once ran on a differently-named session than every other page on the
 * same host, which the customer saw as a 419 on the storefront login form and
 * as a login that never took effect anywhere else. These pin both halves.
 */
it('scopes the session cookie to the host on storefront pages and fortify auth routes', function (string $path) {
    get($path);

    expect(app('session.store')->getName())
        ->toEndWith('-'.Str::slug(str_replace('.', '-', parse_url(config('app.url'), PHP_URL_HOST))));
})->with(['/', '/login', '/register', '/forgot-password']);

it('gives a storefront page and the login form the same session cookie', function () {
    get('/');
    $storefront = app('session.store')->getName();

    get('/login');

    expect(app('session.store')->getName())->toBe($storefront);
});

it('keeps a customer signed in on the storefront after logging in on the login form', function () {
    Route::middleware('web')->get('/__session-probe', fn () => ['id' => auth()->id()]);

    $customer = User::factory()->create(['password' => 'secret123']);

    post('/login', ['email' => $customer->email, 'password' => 'secret123'])
        ->assertRedirect();

    get('/')->assertOk();

    get('/__session-probe')
        ->assertOk()
        ->assertExactJson(['id' => $customer->id]);
});
