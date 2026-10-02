<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Support\Seo\Sitemap;
use App\Support\Seo\Url;
use App\Support\Themes;
use App\Support\ThemeSettings;
use Illuminate\Support\Facades\File;

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

/**
 * Every storefront template, with Blade comments taken out.
 *
 * The comments matter: the chat widget's own docblock names @fluxScripts while
 * explaining why the rest of the themes no longer emit it, and a test that read
 * its own prose as a directive would be useless.
 */
function storefrontTemplates(): array
{
    $files = array_merge(File::allFiles(resource_path('views/frontend')), File::allFiles(base_path('themes')));

    $templates = [];

    foreach ($files as $file) {
        if (! str_ends_with($file->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = preg_replace('/\{\{--.*?--\}\}/s', '', $file->getContents());

        $templates[str_replace(resource_path('views').'\\', '', $file->getRealPath())] = $contents;
    }

    return $templates;
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
        ->and($body)->toContain('pf-loader-mark')
        ->and($body)->toContain('pf-loader-track');
});

it('keeps the loader hidden until the first-visit flag says otherwise', function () {
    // All of this sits on top of a display:none default: no JS, no flag, no
    // loader — nothing to undo, nothing to flash. The reveal is one rule, and
    // the sheet is a full viewport by the time it is shown.
    //
    // The class that reveals it is the first-visit one. The curtain is the
    // opening of this theme's home page, and it plays on the first visit whether
    // or not the page turned out to be slow — a fast page is not a reason to
    // skip it, it is the case where holding the page costs nothing measurable.
    $css = file_get_contents(base_path('themes/portfolio/assets/style.css'));

    expect($css)->toMatch('/\.theme-portfolio \.pf-loader\{[^}]*display:none/s')
        ->and($css)->toMatch('/\.pf-first-visit \.theme-portfolio \.pf-loader\{[^}]*display:flex/s')
        ->and($css)->not->toMatch('/\.pf-slow-visit \.theme-portfolio \.pf-loader\{/s');
});

it('raises the loader on the first visit rather than waiting to find out', function () {
    // There is no speed check in the decision. The page is not measured and the
    // loader is not held back to see whether it was slow — the flag alone arms
    // it, and it is up from the first paint, because a visitor who is new here
    // is owed the opening and a visitor who has been here is not made to wait
    // through the test of it.
    $html = portfolioHome();

    expect($html)->toContain("classList.add('pf-first-visit')")
        ->and($html)->not->toContain('SLOW_MS')
        ->and($html)->not->toContain('pf-slow-visit');
});

it('carries the site own mark and a hairline that fills as the page settles', function () {
    // The curtain is the site introducing itself, not a device animation played
    // over it: the same monogram and name the hero is built from, on one ink
    // sheet, with a hairline filling from the left underneath. The bar's duration
    // is the loader's whole length — it finishes as the sheet lifts — which is
    // why it is one value matched to the flag's floor rather than two that drift.
    $css = file_get_contents(base_path('themes/portfolio/assets/style.css'));

    expect($css)->toMatch('/\.theme-portfolio \.pf-loader\{[^}]*background:#06080f[^}]*flex-direction:column/s')
        ->and($css)->toMatch('/\.theme-portfolio \.pf-loader-mark\{[^}]*align-items:center[^}]*display:flex/s')
        ->and($css)->toMatch('/\.theme-portfolio \.pf-loader-monogram\{[^}]*animation:pf-loader-rise/s')
        ->and($css)->toMatch('/\.theme-portfolio \.pf-loader-name\{[^}]*text-transform:uppercase[^}]*letter-spacing:\.22em/s')
        ->and($css)->toMatch('/\.theme-portfolio \.pf-loader-track\{[^}]*height:1px/s')
        ->and($css)->toMatch('/\.theme-portfolio \.pf-loader-bar\{[^}]*animation:pf-loader-fill 1\.4s/s')
        ->and($css)->toMatch('/@keyframes pf-loader-fill\{0%\{transform:scaleX\(0\)\}100%\{transform:scaleX\(1\)\}\}/s')
        ->and($css)->toMatch('/\.theme-portfolio \.pf-loader\.is-done\{[^}]*opacity:0[^}]*pointer-events:none/s');
});

it('shows the mark it has, as a lockup on the sheet', function () {
    // The lockup is assembled from the site own monogram and name — the two the
    // hero is built from — so the curtain is the same mark the page reveals. The
    // divider between them is the only element that can be stranded, and it only
    // renders alongside the monogram, so a site without one gets no line pointing
    // at nothing.
    $html = portfolioHome();

    expect($html)->toContain('pf-loader-mark')
        ->and($html)->toContain('pf-loader-monogram')
        ->and($html)->toContain('pf-loader-name')
        ->and($html)->toContain('pf-loader-rule')
        ->and($html)->toContain('pf-loader-track')
        ->and($html)->toContain('pf-loader-bar');
});

it('dismisses the loader with a ceiling so a stalled image cannot strand anyone', function () {
    // `load` waits on every subresource, including a tracking pixel far below
    // the fold. The ceiling is the way out for a page that is readable long
    // before that request finishes.
    $html = portfolioHome();

    expect($html)->toContain('setTimeout(lift, MAX_MS)')
        ->and($html)->toContain("addEventListener('load', lift)");
});

it('holds the loader for the length of its own animation', function () {
    // The floor is unconditional, because the sheet is always raised now: MIN_MS
    // is measured from the first paint rather than from whenever `load` happens
    // to fire, so a page that is ready in 50ms does not cut the CRT off at one
    // frame. `Math.max` is the other half — it is the reason the floor can be
    // expressed as a duration since the start rather than a second delay stacked
    // on top of the exit.
    $html = portfolioHome();

    expect($html)->toContain('var MIN_MS = 1450')
        ->and($html)->toContain('Math.max(0, MIN_MS - (Date.now() - started))')
        ->and($html)->not->toContain('SHOW_MIN_MS');
});

it('never hides content behind a loader it did not need', function () {
    // The thing being optimised is the largest contentful paint, and on this
    // page that is the name in the hero. It has to be legible from the first
    // frame: no hidden text, no character-at-a-time typing, no opacity ramp on
    // anything the visitor is waiting to read.
    $html = portfolioHome();

    expect($html)->toContain('pf-typewriter')
        ->and($html)->not->toContain('data-typewriter')
        ->and($html)->not->toContain('visibility:hidden');
});

it('sends Flux to a template that uses it, and to nothing else', function () {
    // Flux's runtime is ~131 KB, so it used to be pulled in by every template on
    // the off-chance that one of them needed it. Twenty-eight of them did not.
    // Stripping it is only safe as long as the three that do still ask for it,
    // and a template that grows a flux: component later has to ask again — so
    // this is asserted in both directions rather than trusting the current list.
    $needs = array_keys(array_filter(
        storefrontTemplates(),
        fn ($contents) => str_contains($contents, '<flux:'),
    ));

    $asks = array_keys(array_filter(
        storefrontTemplates(),
        fn ($contents) => str_contains($contents, '@fluxScripts'),
    ));

    expect($needs)->not->toBeEmpty()
        ->and($needs)->each->toBeIn($asks)
        ->and(array_diff($asks, $needs))->toBe([]);
});

it('never asks a storefront page for the chat widget it now fetches', function () {
    // The bubble used to be <livewire:frontend.chat-widget />, which is what
    // dragged Livewire onto pages that had no other reason to load it. The
    // replacement is a static button plus a fetch, and if a template quietly
    // goes back to the component the saving is gone with nobody noticing.
    $components = array_keys(array_filter(
        storefrontTemplates(),
        fn ($contents) => str_contains($contents, '<livewire:frontend.chat-widget'),
    ));

    expect($components)->toBe([]);
});

it('needs no <noscript>, because the hidden default takes the loader away', function () {
    // With JavaScript off there is no flag and no slow check, so the class that
    // reveals the loader is never added, so the display:none default stands and
    // nothing is ever shown. That is the whole mechanism; a <noscript> block
    // would only be undoing a sheet that was never meant to be up in the first
    // place.
    $css = file_get_contents(base_path('themes/portfolio/assets/style.css'));

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
    $css = file_get_contents(base_path('themes/portfolio/assets/style.css'));

    expect($css)->toMatch('/\.pf-loader\.is-done\s*\{[^}]*pointer-events:\s*none/s')
        ->and(portfolioHome())->toContain('removeChild(loader)');
});

it('hides no page content behind the loader', function () {
    // The sheet is opaque and covers a page that is laid out and painted from
    // the start, so lifting it is one opacity change and no layout — and there
    // is no opacity: 0 on the content that could be left there if the script
    // never ran.
    $css = file_get_contents(base_path('themes/portfolio/assets/style.css'));

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
