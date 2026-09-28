<?php

use App\Models\Setting;
use App\Support\Seo\Sitemap;
use App\Support\Seo\Url;
use App\Support\Themes;
use App\Support\ThemeSettings;

/*
|--------------------------------------------------------------------------
| The portfolio page loader
|--------------------------------------------------------------------------
|
| The one screen shown while the page's CSS, fonts and images come in. The tests
| are mostly about the ways it can go wrong, because the failure that matters is
| a visitor staring at a blank sheet: the lift has to happen on every path out,
| including the ones where the page never finishes loading.
|
*/

beforeEach(function () {
    Setting::set('site_theme', 'portfolio');
});

/** The rendered portfolio home page, as a crawler would get it. */
function portfolioHome(): string
{
    return test()->get('/')->assertOk()->getContent();
}

it('shows the loader on the first paint, before any script has run', function () {
    // Up from the very first paint rather than added by JS: a loader that has to
    // wait for a script to appear flashes the content and then covers it, which
    // is the opposite of smooth.
    $html = portfolioHome();

    expect($html)->toContain('data-pf-loader')
        ->and($html)->toContain('pf-loader-bar')
        ->and($html)->toContain('is-done');
});

it('dismisses the loader with a ceiling so a stalled image cannot strand anyone', function () {
    // `load` waits on every subresource, including a tracking pixel far below
    // the fold. The ceiling is the way out for a page that is readable long
    // before that request finishes.
    $html = portfolioHome();

    expect($html)->toContain('setTimeout(lift, MAX_MS)')
        ->and($html)->toContain("addEventListener('load', lift)");
});

it('holds the loader for a moment so a cached page does not flicker', function () {
    $html = portfolioHome();

    expect($html)->toContain('MIN_MS')
        ->and($html)->toContain('Math.max(0, MIN_MS');
});

it('takes the loader away entirely when JavaScript is off', function () {
    // The one path no script on the page can fix.
    $html = portfolioHome();

    expect($html)->toContain('<noscript>')
        ->and($html)->toContain('.theme-portfolio .pf-loader { display: none; }');
});

it('only ever lifts the loader once', function () {
    // The `load` event and the ceiling can both fire, and on a slow connection
    // they can fire close together; a second run would restart the fade on an
    // element already on its way out.
    $html = portfolioHome();

    expect($html)->toContain('if (lifted) return;')
        ->and($html)->toContain('clearTimeout(ceiling)');
});

it('costs the page no extra request', function () {
    // "Smooth" and "how much does this load" are the same question here: the
    // styles ride along in the theme's own stylesheet and the lift is inline in
    // HTML that has already arrived, so a loader waiting on a file of its own
    // would add a round trip and give the page a new way to 404.
    expect(portfolioHome())->not->toContain('pf-loader.js');
});

it('gives the page back its clicks the moment the lift starts', function () {
    // A full-viewport element left in the document, even at opacity 0, swallows
    // clicks — so the page is handed back when the lift starts, not when the
    // transition ends, and the element goes away entirely afterwards.
    $css = file_get_contents(public_path('themes/portfolio/style.css'));

    expect($css)->toMatch('/\.pf-loader\.is-done\s*\{[^}]*pointer-events:\s*none/s')
        ->and(portfolioHome())->toContain('removeChild(loader)');
});

it('hides no page content behind the loader', function () {
    // The sheet is opaque and covers a page that is laid out and painted from
    // the start, so lifting it is one opacity change and no layout — and there
    // is no opacity: 0 on the content that could be left there if the script
    // never ran.
    $css = file_get_contents(public_path('themes/portfolio/style.css'));

    expect($css)->not->toMatch('/\.theme-portfolio\s+main[^{]*\{[^}]*opacity:\s*0/s')
        ->and($css)->not->toMatch('/\.theme-portfolio\s+body[^{]*\{[^}]*opacity:\s*0/s');
});

it('shows the loader on the blog page too', function () {
    expect($this->get('/blog')->assertOk()->getContent())->toContain('data-pf-loader');
});

it('leaves the loader to the portfolio theme alone', function () {
    // A loader is a theme's own decision. The storefront does not get one, and
    // the default theme's page is a set of login links — a splash in front of
    // it would be the second thing in the way.
    Setting::set('site_theme', 'ecommerce');
    expect($this->get('/')->assertOk()->getContent())->not->toContain('data-pf-loader');

    Setting::set('site_theme', 'default');
    expect($this->get('/')->assertOk()->getContent())->not->toContain('data-pf-loader');
});

/*
|--------------------------------------------------------------------------
| What a theme asks to be indexed
|--------------------------------------------------------------------------
*/

it('keeps the default theme out of search engines', function () {
    // It is a screen of Admin / Vendor / Delivery login links, which used to be
    // handed a canonical URL and a set of og: tags as though it were a page.
    Setting::set('site_theme', 'default');

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('<meta name="robots" content="noindex, nofollow">')
        ->and($html)->not->toContain('rel="canonical"')
        ->and($html)->not->toContain('property="og:');
});

it('sends noindex exactly once', function () {
    // The auth layout and the seo-meta block both know how to write a robots
    // tag. Asking for both would put two of them in one head, and a crawler is
    // left to pick.
    Setting::set('site_theme', 'default');

    expect(substr_count($this->get('/')->getContent(), 'name="robots"'))->toBe(1);
});

it('says in one place that the default theme is a login screen', function () {
    // The flag and the template are two statements of the same thing, so they
    // are asserted against each other: a home page sending noindex without
    // declaring no_index would still be listed in the sitemap.
    expect(Themes::isIndexable('default'))->toBeFalse()
        ->and(Themes::manifest('default')['no_index'])->toBeTrue();
});

it('leaves a storefront theme indexable', function () {
    foreach (['ecommerce', 'portfolio'] as $slug) {
        expect(Themes::isIndexable($slug))->toBeTrue()
            ->and(Themes::manifest($slug)['no_index'])->toBeFalse();
    }
});

it('treats a theme that does not say as indexable', function () {
    // Omitting the key has to mean "indexable": the alternative is a storefront
    // quietly unlisted because someone spelled the flag wrong.
    expect(Themes::manifest('portfolio')['no_index'])->toBeFalse()
        ->and(Themes::isIndexable('portfolio'))->toBeTrue();
});

it('only reads no_index as a real boolean', function () {
    // A JSON string "true" or the number 1 is someone who meant it, but
    // guessing is how a storefront ends up unlisted by a typo.
    ThemeSettings::merge('portfolio', ['no_index' => 'true']);
    expect(Themes::isIndexable('portfolio'))->toBeTrue();

    ThemeSettings::merge('portfolio', ['no_index' => 1]);
    expect(Themes::isIndexable('portfolio'))->toBeTrue();

    ThemeSettings::merge('portfolio', ['no_index' => true]);
    expect(Themes::isIndexable('portfolio'))->toBeFalse();
});

it('does not count no_index among the settings the owner filled in', function () {
    // It describes the theme rather than configuring it, so it must stay out of
    // the "13 values" count next to the Create button.
    ThemeSettings::merge('default', ['no_index' => true, 'theme_default_intro_text' => 'Hi']);

    expect(ThemeSettings::settings('default'))->not->toHaveKey('no_index')
        ->and(ThemeSettings::settings('default'))->toHaveKey('theme_default_intro_text', 'Hi');
});

it('keeps the site root out of the sitemap while it is a login screen', function () {
    Setting::set('site_theme', 'default');

    expect(Themes::isIndexable())->toBeFalse()
        ->and(collect(Sitemap::entries())->pluck('loc')->all())->not->toContain(Url::url('/'));
});

it('lists the site root again once a storefront theme is active', function () {
    Setting::set('site_theme', 'ecommerce');

    expect(Themes::isIndexable())->toBeTrue()
        ->and(collect(Sitemap::entries())->pluck('loc')->all())->toContain(Url::url('/'));
});

it('rebuilds the cached sitemap when a theme stops wanting to be indexed', function () {
    // The sitemap is cached for a day. Without the flag in its fingerprint, a
    // theme.json edited by hand would keep serving the old list until the cache
    // expired on its own.
    Setting::set('site_theme', 'default');
    expect(Sitemap::xml())->not->toContain('<loc>'.Url::url('/').'</loc>');

    Setting::set('site_theme', 'ecommerce');
    expect(Sitemap::xml())->toContain('<loc>'.Url::url('/').'</loc>');
});
