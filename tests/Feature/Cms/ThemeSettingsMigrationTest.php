<?php

use App\Support\ThemeSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The migration that moved theme settings out of the `settings` table, exercised
 * as data rather than as syntax — it is the one piece of this change that runs
 * against a live site with no way to undo a mistake from the admin screen.
 */
function migration(): object
{
    return require database_path('migrations/2026_09_27_200000_move_theme_settings_into_theme_files.php');
}

function settingRow(string $key, string $value, string $group = 'frontend'): void
{
    DB::table('settings')->insert([
        'key' => $key,
        'value' => $value,
        'type' => 'string',
        'group' => $group,
        'is_public' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('moves a theme row into the theme file as a string', function () {
    settingRow('theme_default_hero_title', 'Welcome home');
    settingRow('site_name', 'Codeware', 'general');

    migration()->up();

    expect(ThemeSettings::get('default', 'theme_default_hero_title'))->toBe('Welcome home')
        ->and(ThemeSettings::all('default'))->not->toHaveKey('site_name')
        ->and(DB::table('settings')->where('key', 'theme_default_hero_title')->exists())->toBeFalse()
        ->and(DB::table('settings')->where('key', 'site_name')->value('value'))->toBe('Codeware');
});

it('writes a repeater as a real json array rather than as its stored text', function () {
    settingRow('theme_portfolio_projects', '[{"title":"Ledger","url":"/ledger"}]');

    migration()->up();

    expect(ThemeSettings::all('portfolio')['theme_portfolio_projects'])
        ->toBe([['title' => 'Ledger', 'url' => '/ledger']])
        ->and(DB::table('settings')->where('key', 'theme_portfolio_projects')->exists())->toBeFalse();
});

it('leaves a scalar that happens to be valid json as a string', function () {
    // A colour or a version is valid json too. Decoding it because of what it
    // looks like rather than what it is would put a bracketed string in the file
    // and blank the field on the next save.
    settingRow('theme_default_version', '1.0');
    settingRow('theme_default_note', '"quoted"');

    migration()->up();

    expect(ThemeSettings::get('default', 'theme_default_version'))->toBe('1.0')
        ->and(ThemeSettings::get('default', 'theme_default_note'))->toBe('"quoted"');
});

it('gives each theme only its own rows when slugs share a prefix', function () {
    settingRow('theme_portfolio_name', 'Robin');
    settingRow('theme_portfolio_about_title', 'About me');

    migration()->up();

    expect(ThemeSettings::get('portfolio', 'theme_portfolio_name'))->toBe('Robin')
        ->and(ThemeSettings::get('portfolio', 'theme_portfolio_about_title'))->toBe('About me');
});

it('leaves a theme row alone when its theme is not installed', function () {
    settingRow('theme_gone_name', 'Kept');

    migration()->up();

    expect(DB::table('settings')->where('key', 'theme_gone_name')->value('value'))->toBe('Kept')
        ->and(ThemeSettings::all('gone'))->toBe([]);
});

it('merges into an existing file instead of replacing it', function () {
    ThemeSettings::merge('default', ['theme_default_footer_text' => 'In the file already']);

    $before = ThemeSettings::all('default');

    settingRow('theme_default_hero_title', 'From the table');

    migration()->up();

    $after = ThemeSettings::all('default');

    // Everything the file held before the move is still there — the bundled
    // skeleton included — with the table's row added on top rather than in
    // place of it.
    expect(ThemeSettings::get('default', 'theme_default_hero_title'))->toBe('From the table')
        ->and($after)->toHaveCount(count($before) + 1)
        ->and(array_diff_key($after, $before))->toBe(['theme_default_hero_title' => 'From the table'])
        ->and(ThemeSettings::get('default', 'theme_default_footer_text'))->toBe('In the file already');
});

it('bumps the settings cache version so the storefront stops reading moved rows', function () {
    Cache::forever('settings:cache-version', 7);

    settingRow('theme_default_hero_title', 'Welcome home');

    migration()->up();

    expect((int) Cache::get('settings:cache-version'))->toBe(8);
});

it('leaves the cache version alone when there was nothing to move', function () {
    Cache::forever('settings:cache-version', 7);

    migration()->up();

    expect((int) Cache::get('settings:cache-version'))->toBe(7);
});

it('puts every theme row back on the way down, lists as json text again', function () {
    settingRow('theme_default_hero_title', 'Welcome home');
    settingRow('theme_portfolio_projects', '[{"title":"Ledger"}]');

    migration()->up();
    migration()->down();

    $rows = DB::table('settings')
        ->whereIn('key', ['theme_default_hero_title', 'theme_portfolio_projects'])
        ->pluck('value', 'key');

    expect($rows['theme_default_hero_title'])->toBe('Welcome home')
        ->and($rows['theme_portfolio_projects'])->toBe('[{"title":"Ledger"}]');
});

it('does not put a site-wide row back on the way down', function () {
    settingRow('site_name', 'Codeware', 'general');

    migration()->up();
    migration()->down();

    expect(DB::table('settings')->where('key', 'site_name')->exists())->toBeTrue();
});
