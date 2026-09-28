<?php

use App\Support\Themes;
use App\Support\ThemeSettings;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // The whole point of the store is that a fresh request reads the file, so
    // start each test from a cold memo rather than whatever the previous one
    // happened to leave in the static.
    ThemeSettings::forget();
});

it('reads the values themselves on the very first read of a file', function () {
    // The memo is written on a miss and returned from a second call, so a store
    // that returned its own memo entry on the first read would hand this a
    // ['stamp' => …, 'values' => …] map instead — which merge() would then
    // write back into the file, corrupting it on the first save of a request.
    expect(ThemeSettings::all('default'))->toBeArray()
        ->and(ThemeSettings::all('default'))->toBe(ThemeSettings::all('default'))
        ->and(ThemeSettings::all('default'))->not->toHaveKey('values')
        ->and(ThemeSettings::all('default'))->not->toHaveKey('stamp');
});

it('keeps a key that is present and blank apart from a key that is missing', function () {
    ThemeSettings::merge('default', ['theme_default_footer_text' => 'Kept']);

    expect(ThemeSettings::get('default', 'theme_default_footer_text'))->toBe('Kept')
        ->and(ThemeSettings::get('default', 'theme_default_missing'))->toBeNull()
        ->and(ThemeSettings::get('default', 'theme_default_missing', 'Fallback'))->toBe('Fallback');
});

it('treats a null in the file as absent rather than as a value', function () {
    ThemeSettings::merge('default', ['theme_default_nulled' => null]);

    expect(ThemeSettings::get('default', 'theme_default_nulled', 'Fallback'))->toBe('Fallback');
});

it('reads an unparseable or non-map file as no values rather than as an error', function () {
    ThemeSettings::merge('default', ['theme_default_hero_title' => 'Before']);
    file_put_contents(ThemeSettings::file('default'), '{"theme_default_hero_title": "Before"');
    ThemeSettings::forget();

    expect(ThemeSettings::all('default'))->toBe([]);

    file_put_contents(ThemeSettings::file('default'), '[1, 2, 3]');
    ThemeSettings::forget();

    expect(ThemeSettings::all('default'))->toBe([]);

    // And a file that has been emptied by hand rather than left unparseable is
    // still an editable file, not a missing one.
    file_put_contents(ThemeSettings::file('default'), '');
    ThemeSettings::forget();

    expect(ThemeSettings::all('default'))->toBe([])
        ->and(ThemeSettings::exists('default'))->toBeTrue();
});

it('has no values at all for a theme that has no file', function () {
    ThemeSettings::delete('default');
    ThemeSettings::forget();

    expect(ThemeSettings::all('default'))->toBe([])
        ->and(ThemeSettings::exists('default'))->toBeFalse()
        ->and(ThemeSettings::get('default', 'theme_default_hero_title', 'Fallback'))->toBe('Fallback');
});

it('decodes a list that is still stored as json text', function () {
    // A copy-paste out of the old settings table, which is exactly how this
    // value used to be held. Reading it as a string would put a bracketed
    // string on the public page.
    ThemeSettings::merge('default', [
        'theme_default_projects' => '[{"title":"Ledger"},{"title":"Books"}]',
    ]);

    expect(ThemeSettings::rows('default', 'theme_default_projects'))->toBe([
        ['title' => 'Ledger'],
        ['title' => 'Books'],
    ]);
});

it('reads a bare string row as the field the caller says it is', function () {
    ThemeSettings::merge('default', ['theme_default_education' => '["Freelance", 42, null]']);

    expect(ThemeSettings::rows('default', 'theme_default_education', 'degree'))->toBe([
        ['degree' => 'Freelance'],
    ]);
});

it('drops that same bare string when the caller names no field for it', function () {
    ThemeSettings::merge('default', ['theme_default_education' => '["Freelance", 42, null]']);

    expect(ThemeSettings::rows('default', 'theme_default_education'))->toBe([]);
});

it('flattens a row to its string fields and drops everything else', function () {
    ThemeSettings::merge('default', [
        'theme_default_projects' => [
            ['title' => '  Ledger  ', 'tags' => ['one', 'two'], 'year' => 2019, 'live' => true],
        ],
    ]);

    expect(ThemeSettings::rows('default', 'theme_default_projects'))->toBe([
        ['title' => 'Ledger', 'year' => '2019', 'live' => '1'],
    ]);
});

it('rejects a json object where a list belongs rather than reading it as one row', function () {
    // What a hand edit that forgot the outer brackets leaves behind. Reading it
    // as a single row would publish the operator's half-finished card.
    ThemeSettings::merge('default', ['theme_default_projects' => '{"title":"Ledger"}']);

    expect(ThemeSettings::rows('default', 'theme_default_projects'))->toBe([]);
});

it('keeps a key the theme form never declared when it saves', function () {
    ThemeSettings::merge('default', ['theme_default_footer_text' => 'Kept']);

    ThemeSettings::merge('default', ['theme_default_hero_title' => 'New']);

    expect(ThemeSettings::all('default'))->toMatchArray([
        'theme_default_footer_text' => 'Kept',
        'theme_default_hero_title' => 'New',
    ]);
});

it('leaves the rest of the file byte-for-byte readable after a save', function () {
    ThemeSettings::merge('default', ['theme_default_hero_title' => 'Readable']);

    expect(ThemeSettings::text('default', 'theme_default_hero_title'))->toBe('Readable')
        ->and(json_decode((string) file_get_contents(ThemeSettings::file('default')), true))
        ->toBeArray();
});

it('sees a file that was replaced from outside the app', function () {
    ThemeSettings::merge('default', ['theme_default_hero_title' => 'First']);
    expect(ThemeSettings::get('default', 'theme_default_hero_title'))->toBe('First');

    // No forget(), no merge(): an editor or a git checkout, mid-request.
    file_put_contents(ThemeSettings::file('default'), json_encode([
        'theme_default_hero_title' => 'Second',
    ], JSON_PRETTY_PRINT));

    expect(ThemeSettings::get('default', 'theme_default_hero_title'))->toBe('Second');
});

it('creates a file only where there is no file, and never overwrites one', function () {
    ThemeSettings::delete('default');

    expect(ThemeSettings::create('default'))->toBeTrue()
        ->and(ThemeSettings::file('default'))->not->toBeNull();

    ThemeSettings::merge('default', ['theme_default_hero_title' => 'Kept']);

    expect(ThemeSettings::create('default', ['theme_default_hero_title' => 'Overwritten']))
        ->toBeFalse()
        ->and(ThemeSettings::get('default', 'theme_default_hero_title'))->toBe('Kept');
});

it('refuses to write into a theme folder that is not there, and does not make one', function () {
    // A slug that is not installed is not a theme this class gets to describe,
    // and creating the folder would leave a file Themes::all() then lists as a
    // theme the owner never installed.
    expect(ThemeSettings::merge('not-installed', ['theme_not_installed_hero_title' => 'x']))->toBeFalse()
        ->and(ThemeSettings::create('not-installed'))->toBeFalse()
        ->and(ThemeSettings::delete('not-installed'))->toBeFalse()
        ->and(ThemeSettings::all('not-installed'))->toBe([])
        ->and(File::isDirectory(Themes::path().'/not-installed'))->toBeFalse();
});

it('refuses a slug that would climb out of the themes folder', function () {
    foreach (['..', '../..', 'default/settings', 'has space', 'sub/dir', '', '/etc'] as $slug) {
        expect(ThemeSettings::isValidSlug($slug))->toBeFalse("{$slug} should be refused");
    }

    expect(ThemeSettings::isValidSlug('default'))->toBeTrue()
        ->and(ThemeSettings::isValidSlug('my-theme_2'))->toBeTrue()
        ->and(ThemeSettings::isValidSlug('Retro'))->toBeTrue();
});

it('keeps a theme\'s manifest when its settings are written into the same file', function () {
    // The store is the theme.json the theme already had, so every save runs past
    // the name, description, version, author and tags sitting in it. A write that
    // replaced the file with the form's fields would leave the theme unnamed in
    // the picker and on the storefront, with no error anywhere to say why.
    $before = Themes::manifest('portfolio');

    expect($before['name'])->not->toBe('')
        ->and($before['version'])->not->toBe('');

    ThemeSettings::merge('portfolio', [
        'theme_portfolio_name' => 'Someone Else',
        'theme_portfolio_projects' => [['title' => 'A Project']],
    ]);

    expect(Themes::manifest('portfolio'))->toBe($before)
        ->and(ThemeSettings::text('portfolio', 'theme_portfolio_name'))->toBe('Someone Else')
        ->and(ThemeSettings::rows('portfolio', 'theme_portfolio_projects', 'title'))->toHaveCount(1);
});

it('tells a theme\'s settings apart from the manifest fields beside them', function () {
    // all() is the whole file, which is what merge() has to write back; settings()
    // is the owner's own configuration, which is what anything counting or
    // listing a theme's values wants. Counting the manifest in would report a
    // freshly created theme as holding five values it was never configured with.
    ThemeSettings::merge('default', ['theme_default_hero_title' => 'Mine']);

    $all = ThemeSettings::all('default');
    $settings = ThemeSettings::settings('default');

    expect($all)->toHaveKey('name')
        ->and($all)->toHaveKey('theme_default_hero_title')
        ->and($settings)->toHaveKey('theme_default_hero_title', 'Mine')
        ->and($settings)->not->toHaveKey('name')
        ->and($settings)->not->toHaveKey('version')
        ->and($settings)->not->toHaveKey('author')
        ->and($settings)->not->toHaveKey('tags')
        // The only thing dropped is the manifest; whatever else the installed
        // theme happens to have configured is still the owner's own.
        ->and(array_keys($settings))->toBe(array_values(array_diff(array_keys($all), ThemeSettings::MANIFEST_KEYS)));
});

it('creates a well-formed manifest when it recreates a missing file', function () {
    // What the Create theme.json button writes has to be a theme.json, not a
    // settings map that happens to be called one: a file with no name in it
    // would leave the recreated theme showing up as its slug.
    ThemeSettings::delete('default');

    expect(ThemeSettings::create('default', ['theme_default_hero_title' => 'Seeded']))->toBeTrue();

    $written = json_decode((string) file_get_contents(ThemeSettings::file('default')), true);

    expect($written)->toHaveKey('name', 'Default')
        ->and($written)->toHaveKey('version', '1.0.0')
        ->and($written)->toHaveKey('tags')
        ->and($written)->toHaveKey('theme_default_hero_title', 'Seeded')
        ->and(Themes::manifest('default')['name'])->toBe('Default');
});
