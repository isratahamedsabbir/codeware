<?php

use App\Models\Setting;
use App\Support\Themes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
 * The error pages in resources/views/errors/ are rendered by the framework's
 * exception handler for any HttpException (404 from a missing route, 403 from
 * Gate::authorize, 419 from a stale CSRF token, 429 from the throttle middleware,
 * …). They carry the status code, a static line of copy and a link home — no
 * queries — so these lock in that the right page is used for each status, that
 * the page stays self-contained, and that the home link is the site's APP_URL
 * rather than the host the error happened to be served on.
 *
 * A storefront 404 is the active theme's own page when it ships one (see
 * ThemeScopedTemplatesTest for that half), so this suite pins a theme with no
 * errors/ folder — which is exactly the case these shared pages exist to serve.
 */
beforeEach(function () {
    // Otherwise the framework renders Ignition instead of the error views.
    config(['app.debug' => false]);

    File::ensureDirectoryExists(Themes::path().'/unthemed');
    Setting::set('site_theme', 'unthemed');
    Themes::forget();

    Route::get('/__errors/{code}', fn (string $code) => abort((int) $code, '', [
        'Retry-After' => '120',
    ]));
});

afterEach(function () {
    File::deleteDirectory(Themes::path().'/unthemed');
    Themes::forget();
});

/*
 * The one thing every error page has to get right: the link back goes to
 * APP_URL. url('/') would answer with the host of the request, which on the
 * admin, vendor or delivery portal is that panel's own host — so a storefront
 * error page rendered on a panel host would send a visitor to a login screen.
 */
it('links back to the site home from APP_URL, not the host the error was served on', function () {
    config(['app.url' => 'http://codeware.test']);

    $adminHost = config('app.admin_host');
    expect($adminHost)->not->toBe(request()->getHost());

    $this->get('http://'.$adminHost.'/this-route-does-not-exist')
        ->assertNotFound()
        ->assertSee('href="http://codeware.test"', escape: false)
        ->assertDontSee('href="http://'.$adminHost.'"', escape: false);
});

/*
 * The Nicepage layout: a large status code, a short line of copy, a round button
 * back home. All three are static strings — the page must stay sayable without a
 * lookup, which is what "Suggested pages" used to break.
 */
it('renders the status code, a line of copy and a way home', function () {
    $response = $this->get('/this-route-does-not-exist')->assertNotFound();

    $response->assertSee('404');
    $response->assertSee('Sorry, page not found');
    $response->assertSee('The page you requested could not be found.');
    $response->assertSee(__('Back to homepage'));
    $response->assertSee('href="'.config('app.url').'"', escape: false);

    $response->assertDontSee('Suggested pages');

    // Still noindex, so a thin error page never gets indexed.
    $response->assertSee('noindex, nofollow', escape: false);
});

/*
 * The code and the copy have to agree — a 403 page that says "page not found"
 * sends a visitor looking for a broken link instead of a permission problem.
 */
it('gives each status its own line of copy', function (int $code, string $title) {
    $this->get('/__errors/'.$code)
        ->assertStatus($code)
        ->assertSee($title);
})->with([
    'unauthorized' => [401, 'Unauthorized'],
    'forbidden' => [403, 'Access denied'],
    'session expired' => [419, 'Your session has expired'],
    'too many requests' => [429, 'Too many requests'],
    'not found' => [404, 'Sorry, page not found'],
    'internal server error' => [500, 'Something went wrong'],
    'service unavailable' => [503, 'We will be back shortly'],
]);

it('renders the matching code for each client error status', function (int $code) {
    $this->get('/__errors/'.$code)
        ->assertStatus($code)
        ->assertSee((string) $code);
})->with([
    'unauthorized' => [401],
    'forbidden' => [403],
    'session expired' => [419],
    'too many requests' => [429],
    'not found' => [404],
]);

it('renders the matching code for each server error status', function (int $code) {
    $this->get('/__errors/'.$code)
        ->assertStatus($code)
        ->assertSee((string) $code);
})->with([
    'internal server error' => [500],
    'service unavailable' => [503],
]);

/*
 * A status with no copy of its own still gets a page, not a blank one — the
 * generic line carries the code so "Error 409" reads as deliberate.
 */
it('falls back to the catch-all page for an unmapped client error', function () {
    $this->get('/__errors/409')
        ->assertStatus(409)
        ->assertSee('409')
        ->assertSee(__('Error :code', ['code' => 409]));
});

it('falls back to the catch-all page for an unmapped server error', function () {
    $this->get('/__errors/502')
        ->assertStatus(502)
        ->assertSee('502')
        ->assertSee(__('Error :code', ['code' => 502]));
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
 * A real crash (dead database, fatal error) is a plain Throwable, so without
 * the render callback in bootstrap/app.php the framework bypasses
 * resources/views/errors entirely and falls back to Symfony's generic page.
 */
it('renders our 500 page for a genuine crash, not just abort(500)', function () {
    Route::get('/__errors/boom', function () {
        throw new RuntimeException('the database is on fire');
    });

    $response = $this->get('/__errors/boom')->assertStatus(500);

    $response->assertSee('500');

    // The page must never carry the exception itself.
    $response->assertDontSee('the database is on fire');
    $response->assertDontSee('RuntimeException');
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
    $errHome = implode('-', ['err', 'home']);

    $this->get('/__errors/boom')
        ->assertStatus(500)
        ->assertDontSee($errHome, escape: false);
});

/*
 * An error page that depends on the compiled asset manifest, the layout or the
 * database will itself fail to render on a fresh deploy or during an outage —
 * which is exactly when it is needed. It has to stay self-contained.
 */
it('keeps the error page shell self-contained', function () {
    $shell = file_get_contents(resource_path('views/components/errors/page.blade.php'));

    // Ignore the file's own docblock, which necessarily names some of these.
    $markup = preg_replace('/\{\{--[\s\S]*?--\}\}/', '', $shell);

    foreach (['@vite', '@flux', '@livewire', '@extends', 'x-layouts', 'x-storefront'] as $dependency) {
        expect($markup)->not->toContain($dependency);
    }
});

/*
 * "Simple" is the requirement, so the page must not quietly grow a database
 * read or a settings lookup back in — that is exactly what the old branded
 * shell did, and it is what made an error page able to fail itself.
 */
it('keeps the error page free of database and settings lookups', function () {
    $shell = file_get_contents(resource_path('views/components/errors/page.blade.php'));

    $markup = preg_replace('/\{\{--[\s\S]*?--\}\}/', '', $shell);

    foreach (['Setting::', 'App\Models', 'DB::', 'Cache::'] as $dependency) {
        expect($markup)->not->toContain($dependency);
    }
});
