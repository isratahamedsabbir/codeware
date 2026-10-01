<?php

use App\Livewire\Admin\ThemeSettings\Index as ThemeSettingsIndex;
use App\Models\Setting;
use App\Models\User;
use App\Support\ThemeFont;
use App\Support\Themes;
use App\Support\ThemeSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| The typeface a storefront theme renders in
|--------------------------------------------------------------------------
|
| Set per theme from Admin → Theme Settings → Typography, stored in that theme's
| own theme.json alongside its other settings.
|
| The properties worth pinning down, roughly in order of how quietly they would
| break:
|
|   - THEME_DEFAULT is a real option and means "leave this theme alone". It is
|     what every theme has before anyone opens the screen, so if an untouched
|     theme emitted a font-family the feature would have silently restyled three
|     themes on install. The assertion is that nothing is emitted at all.
|   - The override beats the theme's own hard-coded font. This is the one that
|     cannot be checked by reading the setting back, because the setting is
|     always saved correctly even when the CSS loses the cascade — the picker
|     would appear to work and the page would not change. So the emitted rule is
|     asserted to outrank each of the three ways a theme declares its font.
|   - The @font-face is emitted rather than assumed, because which stylesheet a
|     page loads depends on the theme: a theme that ships its own never sees
|     resources/css/fonts.css, so a face declared only there does not exist on
|     its pages.
|   - The woff2 files exist, because a face pointing at a missing file 404s
|     rather than throwing and the page quietly renders in the fallback.
|
*/

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);

    // A theme's font lives in its real theme.json, so every test here writes to
    // the repository. Snapshotted and put back below rather than merely
    // unsetting the key: whatever was in the file beforehand is the owner's,
    // not this suite's to rewrite.
    $this->themeJsonBefore = [];

    foreach (array_keys(Themes::all()) as $slug) {
        $file = ThemeSettings::file($slug);

        if ($file !== null && File::exists($file)) {
            $this->themeJsonBefore[$slug] = File::get($file);
        }
    }
});

afterEach(function () {
    foreach ($this->themeJsonBefore ?? [] as $slug => $contents) {
        File::put(ThemeSettings::file($slug), $contents);
    }

    ThemeSettings::forget();
    Themes::forget();
});

/**
 * The public home page, for the given active theme.
 */
function storefrontHome(string $theme): string
{
    Setting::set('site_theme', $theme);

    return test()->get('/')->assertOk()->getContent();
}

/**
 * Store a theme's chosen font the way the settings screen does.
 */
function chooseFontFor(string $theme, string $font): void
{
    // merge() writes the file and forgets the read memo on its way out, so
    // there is nothing to bust by hand afterwards.
    ThemeSettings::merge($theme, [ThemeSettings::keyFor('font', $theme) => $font]);
}

/**
 * The marker for the override this feature emits, and the thing that must be
 * absent when a theme has not chosen a font.
 *
 * Narrower than it looks. The themes' own templates inline a
 * `<style>[x-cloak]{display:none!important}</style>`, so "no !important in the
 * page" is not a statement this feature can make — and the compiled CSS a theme
 * may inline carries font-family declarations of its own. This exact line is
 * emitted by partials/head.blade.php and by nothing else.
 */
function storefrontOverride(): string
{
    return 'body { font-family:';
}

it('offers the theme default first, then the faces', function () {
    $options = ThemeFont::options();

    expect(array_key_first($options))->toBe(ThemeFont::THEME_DEFAULT)
        ->and($options[ThemeFont::THEME_DEFAULT])->toBe('Theme default')
        ->and(array_keys($options))->toBe(['', 'system', 'trebuchet', 'plus-jakarta', 'instrument-sans', 'roboto']);
});

it('serves every self-hosted face it offers from public/fonts', function () {
    // "Self-hosted" is the claim that makes these options cost no third-party
    // connection, so it is asserted against the filesystem rather than trusted.
    $urls = [];

    foreach (['system', 'trebuchet', 'plus-jakarta', 'instrument-sans', 'roboto'] as $value) {
        foreach (ThemeFont::facesFor($value) as $faces) {
            foreach ($faces as $face) {
                $urls[] = $face['url'];
            }
        }
    }

    expect($urls)->not->toBeEmpty();

    foreach (array_unique($urls) as $url) {
        $path = public_path(ltrim($url, '/'));

        expect(is_file($path))->toBeTrue("{$url} is missing from public/")
            ->and(file_get_contents($path, false, null, 0, 4))->toBe('wOF2');
    }
});

it('resolves a blank or unrecognised value to the theme keeping its own font', function () {
    // Blank is a deliberate choice here, not an error, and an unknown value has
    // to mean the same thing: "no opinion". Resolving either to a font would
    // restyle a theme nobody asked to change.
    expect(ThemeFont::stackFor(null))->toBeNull()
        ->and(ThemeFont::stackFor(''))->toBeNull()
        ->and(ThemeFont::stackFor('a-face-that-does-not-exist'))->toBeNull()
        ->and(ThemeFont::normalize('a-face-that-does-not-exist'))->toBe(ThemeFont::THEME_DEFAULT)
        ->and(ThemeFont::normalize(['not', 'a', 'string']))->toBe(ThemeFont::THEME_DEFAULT)
        ->and(ThemeFont::normalize('roboto'))->toBe(ThemeFont::ROBOTO);
});

it('describes no file for a system face or for the theme default', function () {
    // A system face has no file, so declaring one would be inventing a download.
    foreach ([ThemeFont::THEME_DEFAULT, ThemeFont::SYSTEM, ThemeFont::TREBUCHET, 'a-face-that-does-not-exist'] as $value) {
        expect(ThemeFont::facesFor($value))->toBe([])
            ->and(ThemeFont::preloadFor($value))->toBeNull();
    }
});

it('emits nothing at all for a theme that has not chosen a font', function () {
    // The no-regression property. A theme's font is chosen to go with its
    // design, and a picker that overwrote that on install would be a bug.
    foreach (['default', 'ecommerce', 'portfolio'] as $theme) {
        expect(storefrontHome($theme))->not->toContain(storefrontOverride(), escape: false);
    }
});

it('overrides the font of whichever theme is active', function () {
    chooseFontFor('portfolio', ThemeFont::ROBOTO);

    expect(storefrontHome('portfolio'))
        ->toContain("body { font-family: 'Roboto', 'Helvetica Neue', Arial, sans-serif !important; }", escape: false)
        ->toContain("font-family: 'Roboto';", escape: false);
});

it('overrides a font the theme declared on a class, not on body', function () {
    // The reason the emitted rule is marked important. Portfolio declares
    // Instrument Sans on a class it puts on <body> and ecommerce on a
    // `font-storefront` utility; both outrank a plain `body` selector, so a rule
    // without the marker would lose the cascade while the setting still saved
    // correctly — a picker that looks like it works and changes nothing.
    foreach (['portfolio', 'ecommerce'] as $theme) {
        chooseFontFor($theme, ThemeFont::ROBOTO);

        $home = storefrontHome($theme);

        expect($home)
            ->toMatch('/<body[^>]*class="[^"]*(theme-'.$theme.'|font-storefront)/')
            ->and($home)->toContain(storefrontOverride(), escape: false)
            ->and($home)->toContain('!important;', escape: false);
    }
});

it('leaves the element in charge of its own font alone', function () {
    // The marker applies to <body> only, so it changes what the page inherits.
    // A descendant that names its own font — portfolio's .pf-mono, a display
    // heading — keeps it, because a descendant's own declaration outranks an
    // inherited value however the ancestor was set.
    chooseFontFor('portfolio', ThemeFont::ROBOTO);

    $home = storefrontHome('portfolio');

    expect($home)
        ->toContain('pf-mono', escape: false)
        // and the override is a bare body rule, not a universal one
        ->not->toContain('* { font-family', escape: false);
});

it('applies a choice to one theme without touching another', function () {
    // Per theme, not global: the whole point of storing it in that theme's own
    // theme.json.
    chooseFontFor('portfolio', ThemeFont::PLUS_JAKARTA);

    expect(storefrontHome('portfolio'))->toContain("'Plus Jakarta Sans', ui-sans-serif", escape: false)
        ->and(storefrontHome('default'))->not->toContain(storefrontOverride(), escape: false);
});

it('declares the face it names, so the family exists on every theme', function () {
    // Which stylesheet a page loads depends on the theme, so a face declared only
    // in resources/css/fonts.css does not exist on a theme that ships its own
    // stylesheet. Picking Plus Jakarta on portfolio would otherwise download
    // nothing and render the fallback.
    foreach ([ThemeFont::PLUS_JAKARTA, ThemeFont::INSTRUMENT_SANS, ThemeFont::ROBOTO] as $font) {
        chooseFontFor('default', $font);

        $home = storefrontHome('default');

        foreach (ThemeFont::facesFor($font) as $faces) {
            foreach ($faces as $face) {
                expect($home)->toContain("src: url('{$face['url']}') format('woff2');", escape: false);
            }
        }
    }
});

it('preloads only the latin subset, and only when a webfont is chosen', function () {
    // Preloading a subset the page may never use is a request the preloader
    // cannot cancel once started, so latin-ext is left to the unicode-range check
    // that happens anyway.
    chooseFontFor('default', ThemeFont::ROBOTO);

    $home = storefrontHome('default');

    expect($home)
        ->toContain('<link rel="preload" href="/fonts/roboto-latin.woff2" as="font" type="font/woff2" crossorigin>', escape: false)
        ->not->toContain('roboto-latin-ext.woff2" as="font"', escape: false);
});

it('preloads no font at all when a system face is chosen', function () {
    chooseFontFor('default', ThemeFont::SYSTEM);

    $home = storefrontHome('default');

    // No font file is coming, so a preload would be a promise the browser keeps
    // and cannot use.
    expect($home)
        ->toContain('body { font-family: ui-sans-serif, system-ui', escape: false)
        ->not->toContain('as="font"', escape: false)
        ->not->toContain('@font-face', escape: false);
});

it('preloads the chosen face instead of the one the theme would have used', function () {
    // The default theme preloads Plus Jakarta today. If it picked Roboto it would
    // be two preloads for two files where only one is ever drawn.
    chooseFontFor('default', ThemeFont::ROBOTO);

    expect(storefrontHome('default'))
        ->not->toContain('plus-jakarta-sans-latin.woff2" as="font"', escape: false);
});

it('keeps a system fallback behind every webfont it names', function () {
    // A webfont can fail to arrive — a bad deploy, a proxy that eats the woff2. A
    // stack of only 'Roboto' would then resolve to nothing and the page would
    // render in the browser default, which is not one of the options on offer.
    foreach ([ThemeFont::PLUS_JAKARTA, ThemeFont::INSTRUMENT_SANS, ThemeFont::ROBOTO] as $font) {
        expect(ThemeFont::stackFor($font))->toContain('sans-serif');
    }
});

it('declares each webfont over the weight axis it actually has', function () {
    // A weight outside a declared axis is rendered with the nearest declared
    // weight rather than the one asked for, so 700 in a face claiming 400-500
    // comes out looking like 600.
    chooseFontFor('default', ThemeFont::PLUS_JAKARTA);

    expect(storefrontHome('default'))->toContain('font-weight: 200 800;', escape: false);
});

it('saves the choice into that theme own theme.json', function () {
    Livewire::test(ThemeSettingsIndex::class)
        ->set('settings.theme_default_font', 'instrument-sans')
        ->call('save');

    expect(ThemeSettings::text('default', 'theme_default_font'))->toBe('instrument-sans')
        ->and(ThemeSettings::all('default'))->not->toHaveKey('theme_portfolio_font');
});

it('saves and reloads the choice for every theme, not just the active one', function () {
    // The screen only ever renders the selected theme, so a picker that was
    // quietly missing from any other theme's form would go unnoticed by a test
    // that never selects that theme. Each slug is selected, typed into and read
    // back here for exactly that reason.
    foreach (array_keys(Themes::all()) as $slug) {
        Setting::set('site_theme', $slug);

        Livewire::test(ThemeSettingsIndex::class)
            ->set('settings.theme_'.$slug.'_font', 'roboto')
            ->call('save');

        expect(ThemeSettings::text($slug, 'theme_'.$slug.'_font'))
            ->toBe('roboto', 'theme '.$slug.' did not save its own font');

        // And the next mount has to hand it back, or the picker would show
        // "Theme default" over a font that is already in effect.
        $reloaded = Livewire::test(ThemeSettingsIndex::class)->get('settings');

        expect($reloaded['theme_'.$slug.'_font'])->toBe('roboto', 'theme '.$slug.' did not reload its font');
    }
});

it('offers the picker, reachable from the section menu, on every theme', function () {
    foreach (array_keys(Themes::all()) as $slug) {
        Setting::set('site_theme', $slug);

        $html = Livewire::test(ThemeSettingsIndex::class)->html();

        // The label proves the field renders and the nav entry proves it can be
        // reached — a panel behind no menu button is a setting nobody finds.
        expect($html)
            ->toContain('Body Font')
            ->toContain("open('typography')");

        // The picker has to live *inside* the Typography panel, and that panel
        // has to be reachable on its own. Both of those are invisible to a
        // page-wide substring search: a panel left unclosed around it parks the
        // field inside a sibling panel, so the markup, the label and the menu
        // button are all present and correct while the field never appears on
        // screen. So the field is looked up through the DOM instead.
        $dom = new DOMDocument;

        @$dom->loadHTML('<!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_NOERROR | LIBXML_NOWARNING);

        $xpath = new DOMXPath($dom);

        $panel = $xpath->query(
            '//*[@role="tabpanel" and contains(@x-show, "typography")]'
        )->item(0);

        expect($panel)->not->toBeNull('theme '.$slug.' has no Typography panel');

        // And it must not itself be buried in another panel, which is exactly
        // how the ecommerce one went missing.
        for ($node = $panel->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
            expect($node->getAttribute('role'))
                ->not->toBe('tabpanel', 'theme '.$slug.' nests its Typography panel inside another panel');
        }

        // The colon in wire:model makes the attribute namespaced as far as an
        // XPath predicate is concerned, and the lookup comes back empty, so the
        // candidates are walked in PHP instead.
        $key = 'settings.theme_'.$slug.'_font';
        $select = null;

        foreach ($xpath->query('.//select', $panel) as $candidate) {
            if ($candidate->getAttribute('wire:model') === $key) {
                $select = $candidate;
                break;
            }
        }

        expect($select)->not->toBeNull('theme '.$slug.' has no font select in its own Typography panel');

        // The picker's own options, read out of that select — the page holds
        // other selects too, so a page-wide match would pass even if this
        // theme's select offered the wrong list.
        $offered = [];

        foreach ($xpath->query('.//option', $select) as $option) {
            $offered[$option->getAttribute('value')] = trim($option->textContent);
        }

        expect($offered)->toEqual(ThemeFont::options());
    }
});

it('leaves the admin panel font alone', function () {
    // Two separate settings on two separate screens. A theme's typeface must not
    // reach the panel, and the panel's must not reach the storefront.
    Setting::set('admin_font', 'roboto');
    chooseFontFor('default', ThemeFont::THEME_DEFAULT);

    $home = storefrontHome('default');

    expect($home)->not->toContain('roboto', escape: false);
});
