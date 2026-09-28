<?php

use App\Models\Setting;
use App\Support\Themes;
use App\Support\ThemeSettings;

/*
|--------------------------------------------------------------------------
| The theme helpers
|--------------------------------------------------------------------------
|
| These are the read side of a theme's own theme.json, and the reason a theme
| can be written once and work in every theme slot on the site. The tests below
| are mostly about one property: a bare field name and a fully prefixed key are
| the same field, and neither form is a trap for the other.
|
*/

beforeEach(function () {
    ThemeSettings::forget();
    Themes::forget();
});

afterEach(function () {
    ThemeSettings::forget();
    Themes::forget();
});

it('resolves a bare field name to the reading theme own key', function () {
    expect(ThemeSettings::keyFor('hero_title', 'photography'))->toBe('theme_photography_hero_title');
});

it('leaves an already prefixed key exactly as it is', function () {
    // Passing a key through untouched is what stops one theme reading another
    // theme's value out of its own file by accident: the prefix is taken at its
    // word rather than rewritten to the theme doing the reading.
    expect(ThemeSettings::keyFor('theme_portfolio_hero_title', 'photography'))
        ->toBe('theme_portfolio_hero_title')
        ->and(ThemeSettings::keyFor('theme_portfolio_hero_title'))->toBe('theme_portfolio_hero_title');
});

it('resolves a bare field name against the active theme when given no slug', function () {
    expect(ThemeSettings::keyFor('hero_title'))->toBe('theme_'.Themes::active().'_hero_title');
});

it('has no key to resolve for an empty field name', function () {
    expect(ThemeSettings::keyFor('  '))->toBe('');
});

it('reads the same value through either key form', function () {
    ThemeSettings::merge('default', ['theme_default_hero_title' => 'Welcome']);

    expect(ThemeSettings::text('default', 'hero_title'))->toBe('Welcome')
        ->and(ThemeSettings::text('default', 'theme_default_hero_title'))->toBe('Welcome')
        ->and(ThemeSettings::get('default', 'hero_title'))->toBe('Welcome');
});

it('reads the active theme file with no slug named at all', function () {
    Setting::set('site_theme', 'portfolio');
    ThemeSettings::merge('portfolio', ['theme_portfolio_hero_title' => 'Portfolio hero']);

    expect(ThemeSettings::text(null, 'hero_title'))->toBe('Portfolio hero');
});

it('reads a theme setting through the helper without naming the theme', function () {
    ThemeSettings::merge('default', ['theme_default_hero_title' => 'Welcome']);

    expect(theme_setting('hero_title'))->toBe('Welcome')
        ->and(theme_setting('hero_title', 'Untitled'))->toBe('Welcome')
        ->and(theme_setting('theme_default_hero_title'))->toBe('Welcome');
});

it('falls back through the helper default for a missing field', function () {
    expect(theme_setting('no_such_field', 'Untitled'))->toBe('Untitled')
        ->and(theme_setting('no_such_field'))->toBe('');
});

it('keeps a saved blank field blank even when a default is offered', function () {
    // The asymmetry the store is built around, and the reason `??` is the wrong
    // operator in a theme template: a field the owner deliberately cleared is
    // present-and-empty, not absent, so the default does not paper over it. A
    // template that wants one asks at the point of use — theme_color('a', '#000')
    // or theme_setting('a') ?: theme_setting('b').
    ThemeSettings::merge('default', ['theme_default_hero_title' => '   ']);

    expect(theme_setting('hero_title'))->toBe('')
        ->and(theme_setting('hero_title', 'Untitled'))->toBe('')
        ->and(theme_setting_raw('hero_title'))->toBe('   ')
        ->and(theme_color('hero_title', '#0f5132'))->toBe('#0f5132');
});

it('reads another theme file when the helper is given its slug', function () {
    ThemeSettings::merge('default', ['theme_default_hero_title' => 'Default hero']);
    ThemeSettings::merge('portfolio', ['theme_portfolio_hero_title' => 'Portfolio hero']);

    expect(theme_setting('hero_title', '', 'default'))->toBe('Default hero')
        ->and(theme_setting('hero_title', '', 'portfolio'))->toBe('Portfolio hero');
});

it('reads a theme list through the helper as flat string maps', function () {
    ThemeSettings::merge('default', [
        'theme_default_projects' => [
            ['title' => 'Ledger', 'link' => ' /work/ledger '],
            ['title' => 'Books'],
        ],
    ]);

    expect(theme_rows('projects'))->toBe([
        ['title' => 'Ledger', 'link' => '/work/ledger'],
        ['title' => 'Books'],
    ]);
});

it('has no rows for a list that is blank, missing or not a list', function () {
    ThemeSettings::merge('default', [
        'theme_default_projects' => [],
        'theme_default_hero_title' => 'Welcome',
    ]);

    expect(theme_rows('projects'))->toBe([])
        ->and(theme_rows('hero_title'))->toBe([])
        ->and(theme_rows('no_such_field'))->toBe([]);
});

it('names the field a bare string in a list stands for', function () {
    ThemeSettings::merge('default', ['theme_default_education' => ['Fine Art', 'Wedding']]);

    expect(theme_rows('education'))->toBe([])
        ->and(theme_rows('education', 'title'))->toBe([
            ['title' => 'Fine Art'],
            ['title' => 'Wedding'],
        ]);
});

it('hands back the whole file through the helper, manifest included', function () {
    ThemeSettings::merge('default', ['theme_default_hero_title' => 'Welcome']);
    ThemeSettings::merge('portfolio', ['theme_portfolio_hero_title' => 'Portfolio hero']);

    expect(theme_json())->toHaveKey('theme_default_hero_title')
        ->and(theme_json())->toHaveKey('name')
        ->and(theme_json('portfolio'))->toHaveKey('theme_portfolio_hero_title');
});

it('takes a colour through the helper only when it is a hex value', function () {
    ThemeSettings::merge('default', [
        'theme_default_accent_color' => ' #0F5132 ',
        'theme_default_junk_color' => 'rgb(1, 2, 3)',
        'theme_default_blank_color' => '',
    ]);

    expect(theme_color('accent_color'))->toBe('#0F5132')
        ->and(theme_color('junk_color', '#000000'))->toBe('#000000')
        ->and(theme_color('blank_color', '#000000'))->toBe('#000000')
        ->and(theme_color('no_such_field'))->toBeNull();
});

it('accepts the short and long hex forms and rejects anything longer', function () {
    ThemeSettings::merge('default', [
        'theme_default_short' => '#abc',
        'theme_default_eight' => '#0f5132cc',
        'theme_default_six' => '#0f5132',
        'theme_default_nine' => '#0f5132ccd',
    ]);

    expect(theme_color('short'))->toBe('#abc')
        ->and(theme_color('eight'))->toBe('#0f5132cc')
        ->and(theme_color('six'))->toBe('#0f5132')
        ->and(theme_color('nine'))->toBeNull();
});

it('reads the active theme slug and its manifest name', function () {
    expect(theme_slug())->toBe(Themes::active())
        ->and(theme_name())->toBe(Themes::manifest(Themes::active())['name'])
        ->and(theme_manifest())->toHaveKeys(['name', 'description', 'version', 'author', 'tags']);
});

it('builds the full key a theme field is stored under', function () {
    expect(theme_setting_key('hero_title'))->toBe('theme_'.Themes::active().'_hero_title')
        ->and(theme_setting_key('hero_title', 'photography'))->toBe('theme_photography_hero_title')
        ->and(theme_setting_key('theme_portfolio_hero_title'))->toBe('theme_portfolio_hero_title');
});

it('reads a theme json that is missing or unreadable as no values at all', function () {
    // The helper is the only thing a template calls, so this is the case a theme
    // author hits: their file is broken, and the page still has to render.
    ThemeSettings::delete('default');
    ThemeSettings::forget();

    expect(theme_json())->toBe([])
        ->and(theme_setting('hero_title'))->toBe('')
        ->and(theme_rows('projects'))->toBe([])
        ->and(theme_color('accent_color', '#000000'))->toBe('#000000');

    ThemeSettings::merge('default', ['theme_default_hero_title' => 'Welcome']);
    file_put_contents(ThemeSettings::file('default'), '{"theme_default_hero_title": "Welcome"');
    ThemeSettings::forget();

    expect(theme_setting('hero_title'))->toBe('');
});
