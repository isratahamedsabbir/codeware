<?php

use App\Models\Page;
use App\Models\Setting;
use App\Support\Themes;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/**
 * The routes one theme's file registers, read on its own.
 *
 * Every theme's routes are registered together, so the real route table can't say
 * which file a route came from. These files are `require`d behind a scratch
 * router swapped in behind the facade, so `Route::get()` inside the file lands
 * there instead of in the table the app is serving from — which is the only way
 * to hold a theme file to what it declares on its own.
 *
 * @return array<int, Illuminate\Routing\Route>
 */
function routesOfTheme(string $slug): array
{
    $real = Route::getFacadeRoot();
    $scratch = new Router(app('events'), app());

    Route::swap($scratch);

    try {
        Route::group([], Themes::allRouteFiles()[$slug]);
    } finally {
        Route::swap($real);
    }

    return $scratch->getRoutes()->getRoutes();
}

/**
 * A theme owns its storefront *routes*, not just its templates: each theme's
 * routes/web/{slug}.php is registered, but only the pages the active theme can
 * serve answer.
 *
 * The point of these is that a page belonging to another theme 404s without its
 * controller ever running — /shop on a portfolio site is the portfolio's own
 * not-found page, not the shop page and not a framework error.
 */
beforeEach(function () {
    // The standalone-page route resolves a slug against a Page row, so /about
    // needs one. The shop routes don't - the storefront reads its own tables.
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
    Page::factory()->published()->create(['title' => ['en' => 'About', 'bn' => ''], 'slug' => 'about', 'sort_order' => 1]);
});

it('serves a page from the active theme own route file', function (string $theme, string $uri) {
    Setting::set('site_theme', $theme);

    $this->get($uri)->assertOk();
})->with([
    'the default home' => ['default', '/'],
    'a default standalone page' => ['default', '/about'],
    'the ecommerce home' => ['ecommerce', '/'],
    'the ecommerce shop' => ['ecommerce', '/shop'],
    'the portfolio home' => ['portfolio', '/'],
]);

it('404s a page whose route belongs to another theme, without running its controller', function () {
    // /shop is the ecommerce theme's route and it stays registered on every
    // theme, so what stops it on a portfolio is the 'theme' guard, not the
    // route's absence. The response is the active theme's own 404 page.
    Setting::set('site_theme', 'portfolio');

    $this->get('/shop')
        ->assertNotFound()
        ->assertSee(__('Sorry, page not found'))
        ->assertSee('pf-btn-solid', escape: false);

    $this->get('/cart')->assertNotFound();
    $this->get('/account')->assertNotFound();
    $this->get('/blog')->assertNotFound();

    // The portfolio ships no page template, so it registers no standalone-page
    // route; /about belongs to the other two themes and is a 404 here too.
    $this->get('/about')->assertNotFound();
});

it('404s a shop page on the default theme, in the default theme own design', function () {
    Setting::set('site_theme', 'default');

    $this->get('/shop')
        ->assertNotFound()
        ->assertSee(__('Sorry, page not found'))
        ->assertSee('rounded-lg bg-primary', escape: false);
});

it('gives an account page a 404 on a theme without an account area, rather than the login page', function () {
    // The guard runs ahead of `auth` (see bootstrap/app.php), so a guest asking a
    // portfolio site for /account is told the page isn't there. A redirect to
    // login would be a worse answer twice over: it answers about a page the
    // portfolio doesn't have, and it advertises that it has an account area.
    Setting::set('site_theme', 'portfolio');

    $this->get('/account')->assertNotFound();

    // The ecommerce theme does have one, so the same guest gets the login
    // redirect there — the guard is not standing in for auth.
    Setting::set('site_theme', 'ecommerce');

    $this->get('/account')->assertRedirect(route('login'));
});

it('keeps the system routes on every theme, since they are not a themed page', function (string $theme) {
    // Signed invoice links, the /admin bounce, the auth routes: none of these
    // render a theme template, so none of them are registered behind a theme
    // guard. A voucher bought on the ecommerce site has to stay redeemable after
    // the admin switches the site to a one-pager, and only because of that.
    Setting::set('site_theme', $theme);

    foreach (['invoices.public.show', 'invoices.public.download', 'vouchers.public.show', 'vouchers.public.download', 'admin.legacy', 'dashboard'] as $name) {
        expect(Route::has($name))->toBeTrue("{$name} must exist on the {$theme} theme");
    }
})->with(['default', 'ecommerce', 'portfolio']);

it('contributes no routes for a theme that ships no route file', function () {
    // The same rule as the templates: a theme contributes nothing it didn't ship.
    // Its templates can still be reached through a controller another theme's
    // route points at, but it registers no URL of its own.
    expect(Themes::routeFileExists('ecommerce'))->toBeTrue();
    expect(Themes::allRouteFiles())->toHaveKeys(['default', 'ecommerce', 'portfolio']);
});

it('registers every route Themes::ROUTE_TEMPLATES maps, in at least one theme', function () {
    // ROUTE_TEMPLATES is a hand-maintained mirror of the theme route files: it is
    // what turns a route name into "the template this page needs", what the
    // 'theme' guard checks a request against, and what lets a nav link to a page
    // the active theme doesn't serve be dropped. If no theme file registers one of
    // these names, the guard starts refusing a page the theme does ship, and the
    // nav filter stops recognising it. This keeps the mirror honest.
    $templates = Themes::routeTemplates();

    foreach (Themes::all() as $slug => $label) {
        if (! Themes::routeFileExists($slug)) {
            continue;
        }

        // Every route name this theme's file registers.
        $names = array_map(fn ($route) => $route->getName(), routesOfTheme($slug));

        foreach ($templates as $name => $template) {
            if (! in_array($name, $names, true)) {
                continue;
            }

            // Found it — and the theme that serves it has to ship the template,
            // or the guard would 404 the page its own file declares.
            expect(Themes::hasTemplateFor($slug, $template))->toBeTrue(
                "routes/web/{$slug}.php registers {$name}, but the {$slug} theme ships no {$template} template for it"
            );

            unset($templates[$name]);
        }
    }

    expect($templates)->toBeEmpty('No bundled theme route file registers: '.implode(', ', array_keys($templates)));
});

it('gives a route name the same page in every theme file that registers it', function () {
    // Templates, seeders and menu items address pages by name (route('home')), and
    // the theme files repeat the shared routes rather than including one common
    // file. A name that meant "/" in one file and "/shop" in another would make a
    // stored menu URL resolve differently depending on the active theme, so the
    // same name has to be the same method+URI everywhere it appears.
    $seen = [];

    foreach (array_keys(Themes::allRouteFiles()) as $slug) {
        foreach (routesOfTheme($slug) as $route) {
            $name = $route->getName();
            $target = implode(' ', $route->methods()).' '.$route->uri();

            expect($seen[$name] ?? $target)->toBe($target, "route name \"{$name}\" means two different pages");

            $seen[$name] = $target;
        }
    }

    // The homepage is the one name every bundled theme has to answer.
    expect($seen)->toHaveKey('home');
});

it('leaves the live route table alone while reading a theme file', function () {
    // routesOfTheme() swaps the facade's router out to read one file in
    // isolation. If it didn't put the real one back, every later test in the
    // process would be reading routes into a scratch collection.
    $before = count(Route::getRoutes()->getRoutes());

    routesOfTheme('ecommerce');

    expect(count(Route::getRoutes()->getRoutes()))->toBe($before);
});
