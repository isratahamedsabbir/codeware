<?php

use App\Models\Page;
use App\Models\Post;
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
| The one screen shown while the home page's CSS, fonts and images come in — a
| full-viewport black sheet styled as a CRT power-on. It appears once per
| browser: the first time this site is opened, and never again after. The blog
| and its posts open straight on content, without one.
| The tests are mostly about the ways it can go wrong, because the failure
| that matters is a visitor staring at a blank sheet: the lift has to happen
| on every path out, including the ones where the page never finishes loading.
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

it('shows the loader only on the first visit, and the flag is set in the head', function () {
    // The sheet is hidden by default and the pf-first-visit class is the only
    // thing that reveals it. The decision is made in the head — before the body
    // has painted anything — so a visitor who has been here before loads
    // straight onto the page, and a first-time visitor never sees the loader
    // flash off. A flag applied after the loader element already exists would
    // be a flash of the thing being removed.
    $html = portfolioHome();

    expect($html)->toContain('pf-loader-visited')
        ->and($html)->toContain('localStorage')
        ->and($html)->toContain("classList.add('pf-first-visit')")
        ->and($html)->toContain('pf-first-visit');

    // And the gate is in <head>, ahead of the body's loader markup.
    $bodyPos = strpos($html, '<body');
    $head = substr($html, 0, $bodyPos);
    $body = substr($html, $bodyPos);
    expect($head)->toContain('pf-loader-visited')
        ->and($body)->toContain('data-pf-loader')
        ->and($body)->toContain('pf-loader-crt-line')
        ->and($body)->toContain('pf-loader-crt-plate-top')
        ->and($body)->toContain('pf-loader-crt-plate-bottom')
        ->and($body)->toContain('pf-loader-crt-spark');
});

it('keeps the loader hidden until the first-visit gate reveals it', function () {
    // All of this sits on top of a display:none default: no JS, no flag, no
    // loader — nothing to undo, nothing to flash. The reveal is one rule, and
    // the sheet is a full viewport by the time it is shown.
    $css = file_get_contents(public_path('themes/portfolio/style.css'));

    expect($css)->toMatch('/\.theme-portfolio \.pf-loader\{[^}]*display:none/s')
        ->and($css)->toMatch('/\.pf-first-visit \.theme-portfolio \.pf-loader\{[^}]*display:flex/s');
});

it('opens from the middle: the black parts, the seam shows the portfolio', function () {
    // The black is real black, but it lives in two solid plates — top and
    // bottom — that slide apart on the same swing as the white CRT band. The
    // loader sheet itself carries no background, so the widening gap between
    // the plates is transparent: the portfolio underneath shows through the
    // translucent white band as the machine starts, instead of staying hidden
    // behind an opaque wall until the fade.
    $css = file_get_contents(public_path('themes/portfolio/style.css'));

    expect($css)->toMatch('/\.theme-portfolio \.pf-loader-crt-plate-top\{[^}]*background:#000[^}]*top:0/s')
        ->and($css)->toMatch('/\.theme-portfolio \.pf-loader-crt-plate-bottom\{[^}]*background:#000[^}]*bottom:0/s')
        ->and($css)->toMatch('/@keyframes pf-crt-open-top\{0%\{transform:translateY\(0\)\}to\{transform:translateY\(-130%\)\}\}/s')
        ->and($css)->toMatch('/@keyframes pf-crt-open-bottom\{0%\{transform:translateY\(0\)\}to\{transform:translateY\(130%\)\}\}/s')
        ->and($css)->toMatch('/\.theme-portfolio \.pf-loader-crt-line\{[^}]*background:#ffffff99/s')
        ->and($css)->toMatch('/\.theme-portfolio \.pf-loader\.is-done\{[^}]*opacity:0[^}]*pointer-events:none/s');
});

it('dismisses the loader with a ceiling so a stalled image cannot strand anyone', function () {
    // `load` waits on every subresource, including a tracking pixel far below
    // the fold. The ceiling is the way out for a page that is readable long
    // before that request finishes.
    $html = portfolioHome();

    expect($html)->toContain('setTimeout(lift, MAX_MS)')
        ->and($html)->toContain("addEventListener('load', lift)");
});

it('holds the loader for as long as the CRT animation takes to play', function () {
    // The hairline starts at .2s and needs .9s to swell into the field. A floor
    // that is shorter than the animation would cut the effect off at one frame;
    // the floor exists to let the animation complete, so it is chosen to match
    // the animation's own delay plus duration.
    $html = portfolioHome();

    expect($html)->toContain('MIN_MS')
        ->and($html)->toContain('Math.max(0, MIN_MS');
});

it('needs no <noscript>, because the hidden default takes the loader away', function () {
    // With JavaScript off there is no flag, so there is no .pf-first-visit
    // class, so the display:none default stands and nothing is ever shown.
    // That is the whole mechanism; a <noscript> block would only be undoing
    // a sheet that was never meant to be up in the first place.
    $css = file_get_contents(public_path('themes/portfolio/style.css'));

    expect($css)->toMatch('/\.theme-portfolio \.pf-loader\{[^}]*display:none/s')
        ->and(portfolioHome())->not->toContain('<noscript>');
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

it('does not wrap the blog or a post in the loader', function () {
    // The loader is the curtain over a landing — the home page — not a banner
    // over every route. The blog opens straight on content, and a full-viewport
    // sheet put between a reader and the article they just clicked through to is
    // the opposite of a welcome.
    expect($this->get('/blog')->assertOk()->getContent())->not->toContain('data-pf-loader');

    $post = Post::factory()->published()->create([
        'title' => ['en' => 'The Loader Is Home-Only', 'bn' => ''],
    ]);
    Page::factory()->published()->create([
        'title' => ['en' => 'The Loader Is Home-Only', 'bn' => ''],
        'slug' => 'the-loader-is-home-only',
        'type' => 'post',
        'post_id' => $post->id,
    ]);
    expect($this->get('/blog/the-loader-is-home-only')->assertOk()->getContent())
        ->not->toContain('data-pf-loader');
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
