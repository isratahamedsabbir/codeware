<?php

use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Setting;
use App\Support\Frontend;
use App\Support\Themes;
use Illuminate\Support\Facades\File;

/**
 * A theme is self-contained: it renders the pages it ships templates for, and
 * has nothing to do with the rest. These cover both halves of that rule — a
 * page the active theme doesn't have 404s instead of appearing in another
 * theme's design, and the 404 itself comes from the active theme when it ships
 * one, falling back to Laravel's shared error pages when it doesn't.
 */

/**
 * Builds a real theme folder on disk, cleaned up by the afterEach below. A
 * template is written as a stub that only echoes the title — enough for the
 * route to succeed, and deliberately free of any theme chrome so a failure
 * points at template resolution rather than at a missing partial.
 *
 * @param  array<int, string>  $templates
 */
function installBareTheme(string $slug, array $templates = ['home', 'page']): void
{
    File::ensureDirectoryExists(Themes::path().'/'.$slug);

    foreach ($templates as $template) {
        File::put(Themes::path().'/'.$slug.'/'.$template.'.blade.php', '<p>{{ $title ?? "" }}</p>');
    }
}

beforeEach(function () {
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
    Page::factory()->published()->create(['title' => ['en' => 'About', 'bn' => ''], 'slug' => 'about', 'sort_order' => 1]);
    Page::factory()->published()->create(['title' => ['en' => 'Contact', 'bn' => ''], 'slug' => 'contact', 'sort_order' => 2]);
});

afterEach(function () {
    // Never leave a folder these tests created on the real filesystem. Themes
    // caches its folder scan for a day, so the memo/cache has to go too.
    File::deleteDirectory(Themes::path().'/bare');
    File::deleteDirectory(Themes::path().'/broken');
    Themes::forget();
});

it('renders the pages the active theme ships and 404s the ones it does not', function () {
    installBareTheme('bare', ['home', 'page']);

    Setting::set('site_theme', 'bare');
    Themes::forget();

    $this->get('/')->assertOk();
    $this->get('/about')->assertOk();
    $this->get('/contact')->assertOk();

    // No shop template in this theme, and no other theme is allowed to lend it
    // one — this used to resolve to the ecommerce theme's shop page.
    $this->get('/shop')->assertNotFound();
    $this->get('/cart')->assertNotFound();
    $this->get('/blog')->assertNotFound();
});

it('does not fall back to another theme for a home or standalone page', function () {
    installBareTheme('broken', ['home']);

    Setting::set('site_theme', 'broken');
    Themes::forget();

    $this->get('/')->assertOk();

    // The theme has no page.blade.php at all. That has to be a 404, not a 500
    // from an unresolvable view and definitely not the default theme's page.
    $this->get('/about')->assertNotFound();
    $this->get('/contact')->assertNotFound();
});

it('renders each theme own home and page templates rather than one shared fallback', function () {
    Setting::set('site_theme', 'ecommerce');
    $this->get('/about')->assertOk()->assertDontSee('pf-');

    Setting::set('site_theme', 'default');
    $this->get('/about')->assertOk()->assertDontSee('pf-');

    // The portfolio is a one-pager — it ships no page template, so it neither
    // renders CMS pages of its own nor borrows another theme's.
    Setting::set('site_theme', 'portfolio');
    $this->get('/about')->assertNotFound();
});

it('serves the active theme own 404 when it ships one', function () {
    Setting::set('site_theme', 'ecommerce');
    $this->get('/nope-not-a-page')->assertNotFound()
        ->assertSee("We couldn't find that product")
        ->assertSee('Browse the shop');

    Setting::set('site_theme', 'portfolio');
    $this->get('/nope-not-a-page')->assertNotFound()
        ->assertSee('Nothing here')
        ->assertSee('Selected work');
});

it('gives every installed theme its own 404 rather than leaving one to the fallback', function () {
    // A theme without errors/404.blade.php is a gap, not a design decision: the
    // shared page is the safety net, not where a shipped theme should end up.
    foreach (array_keys(Themes::all()) as $slug) {
        Setting::set('site_theme', $slug);

        expect(Themes::errorView(404))->toBe("frontend.themes.{$slug}.errors.404");
    }

    // The default theme owns one too, in its own accent, and points back at the
    // site's own pages rather than the shop or portfolio sections.
    Setting::set('site_theme', 'default');
    Themes::forget();

    $this->get('/nope-not-a-page')
        ->assertNotFound()
        ->assertSee('Page not found')
        // Its own brand, not the ecommerce accent the shell falls back to.
        ->assertSee('#1e7bc4')
        ->assertSee('About')
        ->assertDontSee("We couldn't find that product");
});

it("falls back to Laravel's shared 404 for a theme that ships no error page", function () {
    installBareTheme('bare', ['home', 'page']);

    Setting::set('site_theme', 'bare');
    Themes::forget();

    $this->get('/nope-not-a-page')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertSee('We could not find the page you are looking for');
});

it('keeps the shared error pages for the admin panel and the API', function () {
    Setting::set('site_theme', 'ecommerce');

    // The API answers in JSON, never with a themed storefront page.
    $this->getJson('/api/v1/nope')->assertNotFound()->assertHeader('content-type', 'application/json');

    // A request on the admin host keeps the shared 404 too, rather than the
    // storefront's — the panel is a separate site, not this theme.
    $adminHost = config('app.admin_host');
    expect($adminHost)->not->toBe(request()->getHost());

    $this->get('http://'.$adminHost.'/nope-not-a-page')
        ->assertNotFound()
        ->assertSee('Page not found')
        ->assertDontSee("We couldn't find that product");
});

it('drops a nav link the active theme cannot render, and keeps it on a theme that can', function () {
    MenuItem::create(['group' => 'frontend', 'label' => 'Shop', 'url' => '/shop', 'sort_order' => 0, 'is_active' => true]);
    MenuItem::create(['group' => 'frontend', 'label' => 'About', 'url' => '/about', 'sort_order' => 1, 'is_active' => true]);

    // The default theme has page.blade.php but no shop one.
    Setting::set('site_theme', 'default');
    expect(Frontend::menuItems()->pluck('label')->all())->toBe(['About']);

    Setting::set('site_theme', 'ecommerce');
    expect(Frontend::menuItems()->pluck('label')->all())->toBe(['Shop', 'About']);
});

it('never renders a nav link into a page the active theme does not have', function () {
    // A theme whose home simply prints the nav labels, so the filter can be
    // asserted on a real response rather than only through the accessor.
    installBareTheme('bare');
    File::put(
        Themes::path().'/bare/home.blade.php',
        '@foreach (\App\Support\Frontend::menuItems() as $item)[{{ $item->label }}]@endforeach',
    );

    MenuItem::create(['group' => 'frontend', 'label' => 'Shop', 'url' => '/shop', 'sort_order' => 0, 'is_active' => true]);
    MenuItem::create(['group' => 'frontend', 'label' => 'Anchor', 'url' => '#projects', 'sort_order' => 1, 'is_active' => true]);
    MenuItem::create(['group' => 'frontend', 'label' => 'External', 'url' => 'https://example.com', 'sort_order' => 2, 'is_active' => true]);
    MenuItem::create(['group' => 'frontend', 'label' => 'Odd Path', 'url' => '/terms', 'sort_order' => 3, 'is_active' => true]);

    Setting::set('site_theme', 'bare');
    Themes::forget();

    $this->get('/')
        ->assertOk()
        ->assertSee('[Anchor]')
        ->assertSee('[External]')
        ->assertSee('[Odd Path]')
        ->assertDontSee('[Shop]');
});

it('hides a page link even though the page itself is published in the database', function () {
    // The row is there and published — this theme simply has no page template to
    // render it, so the link goes and the URL 404s.
    expect(Page::where('slug', 'contact')->exists())->toBeTrue();

    installBareTheme('bare', ['home']);

    Setting::set('site_theme', 'bare');
    Themes::forget();

    expect(Frontend::navPages())->toBeEmpty();
    $this->get('/contact')->assertNotFound();
});

it('resolves a nested account template instead of silently falling through', function () {
    Setting::set('site_theme', 'ecommerce');

    // 'account.dashboard' is account/dashboard.blade.php — read as directories,
    // since a plain concatenation looks for a file no theme can ship.
    expect(Themes::view('account.dashboard'))->toBe('frontend.themes.ecommerce.account.dashboard');
    expect(Themes::view('account.profile'))->toBe('frontend.themes.ecommerce.account.profile');

    Setting::set('site_theme', 'default');
    expect(Themes::view('account.dashboard'))->toBeNull();
});
