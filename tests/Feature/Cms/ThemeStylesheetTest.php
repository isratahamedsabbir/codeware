<?php

use App\Models\Setting;
use App\Support\Themes;

/*
|--------------------------------------------------------------------------
| Per-theme stylesheets
|--------------------------------------------------------------------------
|
| A theme is a folder, and its stylesheet lives in it alongside the templates it
| styles: resources/css/themes/{slug}/theme.css, next to routes/web/{slug}.php
| and resources/views/frontend/themes/{slug}/. The two properties worth pinning
| down are that a theme with one is served it, and that a theme without one is
| still styled — the second because a theme installed later as a zip ships
| templates and no stylesheet, and bare HTML is a broken storefront.
|
*/

it('gives every bundled theme its own stylesheet', function (string $theme) {
    expect(Themes::hasStylesheet($theme))->toBeTrue()
        ->and(Themes::storefrontEntry($theme))->toBe("resources/css/themes/{$theme}/theme.css");
})->with(['default', 'ecommerce', 'portfolio']);

it('falls back to the catch-all storefront bundle for a theme that ships no stylesheet', function () {
    // A theme folder with templates in it but no theme.css — the shape a
    // zipped-in third-party theme has. It is styled, just at the price of
    // carrying every other theme's classes along too.
    expect(Themes::hasStylesheet('no-such-theme'))->toBeFalse()
        ->and(Themes::storefrontEntry('no-such-theme'))->toBe('resources/css/storefront.css');
});

it('serves the active theme its own stylesheet rather than the catch-all', function () {
    // The whole point of the split: a portfolio page does not download the
    // ecommerce theme's shop and checkout classes. Resolved for the active theme
    // rather than named, so the head partial needs no branch of its own.
    Setting::set('site_theme', 'portfolio');

    expect(Themes::storefrontEntry())->toBe('resources/css/themes/portfolio/theme.css');
});

it('keeps every theme stylesheet inside its own folder', function () {
    // One stylesheet per theme, and no theme's file sitting in another's folder
    // — a theme has to be able to be zipped up and handed over intact, which
    // only holds if its CSS travels with it.
    $found = [];

    foreach (array_keys(Themes::all()) as $theme) {
        if (Themes::hasStylesheet($theme)) {
            $found[] = $theme;
        }
    }

    expect($found)->toBe(['default', 'ecommerce', 'portfolio']);

    foreach ($found as $theme) {
        // Scans its own templates, and declares no other theme's folder as a
        // source — a stray second theme in here is exactly the regression this
        // arrangement is meant to make impossible.
        $sources = array_values(array_map(
            'trim',
            preg_split('/\R/', (string) file_get_contents(Themes::stylesheetsPath().'/'.$theme.'/theme.css')),
        ));
        $sources = array_values(array_filter(
            $sources,
            fn (string $line) => str_starts_with($line, '@source'),
        ));

        expect($sources)
            ->toContain("@source '../../../views/frontend/themes/{$theme}';")
            ->toHaveCount(2); // its own folder, and the `not` rule for its settings screen
    }
});
