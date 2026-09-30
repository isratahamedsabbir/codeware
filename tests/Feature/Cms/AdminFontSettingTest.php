<?php

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Models\Setting;
use App\Models\User;
use App\Support\AdminFont;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\View\ComponentSlot;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| The panel's typeface
|--------------------------------------------------------------------------
|
| Three options, and the property that defines them is not "which fonts" but
| "which of them does the machine already have". System font and Segoe UI are
| resolved by the browser from what is installed, so selecting either costs no
| request. Roboto is not installed by default on Windows or macOS, so it is a
| self-hosted woff2 with a preload and an @font-face — and a stack that keeps a
| system fallback behind the family name in case the file never arrives.
|
| The tests below mostly exist to hold that split still, because it is the part
| that would rot quietly: a fallback added to a webfont stack for safety is
| exactly the kind of thing a later "cleanup" removes, and a missing @font-face
| does not throw, it just silently renders in the browser default.
|
| It is also admin-only. Nothing here reads a storefront theme, and the
| storefront never reads this — a font setting that leaked across the two would
| change the public site's typography from an admin screen.
|
*/

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

/**
 * The panel head for a given stored setting value.
 */
function adminPanelFor(string $value): string
{
    Setting::set('admin_font', $value);

    return view('layouts.admin', [
        'title' => 'Settings',
        'slot' => new ComponentSlot('<p>panel</p>'),
    ])->render();
}

it('offers exactly three options, with the system font first', function () {
    expect(AdminFont::options())->toBe([
        'system' => 'System font',
        'segoe' => 'Segoe UI',
        'roboto' => 'Roboto',
    ]);
});

it('defaults to letting the OS answer', function () {
    // The default is the deliberate one. A panel that quietly started resolving
    // to a specific face instead of the machine's own would be the setting
    // changing the product rather than the other way round.
    expect(AdminFont::stackFor(null))->toBe(AdminFont::stacks()['system'])
        ->and(AdminFont::stackFor(''))->toBe(AdminFont::stacks()['system'])
        ->and(AdminFont::stackFor(AdminFont::SYSTEM))->toBe(AdminFont::stacks()['system']);
});

it('leads each stack with the face that option is asking for', function () {
    // The system stack leads with system-ui, so a Mac gets San Francisco; the
    // other two name their face first so it wins wherever it is installed.
    expect(AdminFont::stackFor(AdminFont::SYSTEM))->toStartWith('ui-sans-serif, system-ui')
        ->and(AdminFont::stackFor(AdminFont::SEGOE))->toStartWith("'Segoe UI'")
        ->and(AdminFont::stackFor(AdminFont::ROBOTO))->toStartWith("'Roboto'");
});

it('keeps a system fallback behind every webfont it names', function () {
    // Roboto is downloaded, so it can fail to arrive — a bad deploy, a stale
    // CDN edge, a proxy that eats the woff2. A stack of only 'Roboto' would then
    // resolve to nothing and the panel would render in the browser default,
    // which is not one of the options on offer.
    expect(AdminFont::stackFor(AdminFont::ROBOTO))->toContain("'Helvetica Neue'")
        ->and(AdminFont::stackFor(AdminFont::ROBOTO))->toContain('sans-serif');
});

it('ends every stack in a generic family so text always renders', function () {
    foreach (AdminFont::stacks() as $stack) {
        expect($stack)->toEndWith('sans-serif');
    }
});

it('falls back to the system stack for a value that is not one of the three', function () {
    expect(AdminFont::stackFor('a-face-that-does-not-exist'))->toBe(AdminFont::stacks()['system'])
        ->and(AdminFont::normalize('a-face-that-does-not-exist'))->toBe(AdminFont::SYSTEM)
        ->and(AdminFont::normalize(['not', 'a', 'string']))->toBe(AdminFont::SYSTEM);
});

it('normalizes a saved value against the options that exist', function () {
    expect(AdminFont::normalize('roboto'))->toBe(AdminFont::ROBOTO)
        ->and(AdminFont::normalize('segoe'))->toBe(AdminFont::SEGOE)
        ->and(AdminFont::normalize('system'))->toBe(AdminFont::SYSTEM)
        ->and(AdminFont::normalize(null))->toBe(AdminFont::SYSTEM);
});

it('renders the choice on the settings page and saves it', function () {
    Livewire::test(SettingsIndex::class)
        ->assertViewHas('adminFontOptions', fn (array $options) => array_keys($options) === ['system', 'segoe', 'roboto'])
        ->assertSee('Backend Font')
        ->assertSee('Segoe UI')
        ->assertSee('Roboto')
        ->set('settings.admin_font', 'roboto')
        ->call('save');

    expect(Setting::where('key', 'admin_font')->value('value'))->toBe('roboto');
});

it('stores the system font when a value that is not an option is saved', function () {
    Setting::set('admin_font', 'roboto');

    Livewire::test(SettingsIndex::class)
        ->set('settings.admin_font', 'nonsense')
        ->call('save');

    expect(Setting::get('admin_font'))->toBe(AdminFont::SYSTEM);
});

it('defaults the field to the system font on a database that predates the setting', function () {
    // No admin_font row at all. The select would otherwise render with nothing
    // selected, which reads as "unset" rather than as the deliberate default.
    expect(Setting::where('key', 'admin_font')->exists())->toBeFalse();

    expect(Livewire::test(SettingsIndex::class)->get('settings.admin_font'))->toBe(AdminFont::SYSTEM);
});

it('renders the panel in each chosen face', function () {
    foreach (['system', 'segoe', 'roboto'] as $value) {
        expect(adminPanelFor($value))->toContain('--font-sans: '.AdminFont::stacks()[$value], escape: false);
    }
});

it('downloads nothing for either system option', function () {
    // These are the reasons the default costs no request: no preload to fire and
    // no @font-face for the browser to match against.
    foreach ([AdminFont::SYSTEM, AdminFont::SEGOE] as $value) {
        expect(adminPanelFor($value))
            ->not->toContain('as="font"', escape: false)
            ->not->toContain('@font-face', escape: false);
    }
});

it('preloads and declares Roboto when it is the chosen option', function () {
    $panel = adminPanelFor(AdminFont::ROBOTO);

    expect($panel)
        // The latin subset is preloaded...
        ->toContain('<link rel="preload" href="/fonts/roboto-latin.woff2" as="font" type="font/woff2" crossorigin>', escape: false)
        // ...but not latin-ext, which a preloader cannot cancel once started.
        ->not->toContain('roboto-latin-ext.woff2" as="font"', escape: false)
        // Both are still declared, so the ext file is fetched on demand. Quoted,
        // because an unquoted url() is only valid while the path stays free of
        // spaces and parentheses.
        ->toContain("src: url('/fonts/roboto-latin-ext.woff2') format('woff2');", escape: false)
        ->toContain("src: url('/fonts/roboto-latin.woff2') format('woff2');", escape: false);
});

it('splits Roboto into two ranges so the ext file is spent only when a character needs it', function () {
    $panel = adminPanelFor(AdminFont::ROBOTO);

    expect($panel)
        // Latin covers ordinary panel text...
        ->toContain('unicode-range: U+0000-00FF,', escape: false)
        // ...and ext only covers characters the plain latin range does not, so
        // a page with none of them never downloads its 29 KB.
        ->toContain('unicode-range: U+0100-02BA,', escape: false);

    expect(AdminFont::facesFor(AdminFont::ROBOTO)[AdminFont::ROBOTO])->toHaveCount(2);
});

it('declares Roboto as a variable font so one file covers every weight', function () {
    // font-weight is an axis, not a list of files: the panel uses 400 through
    // 700 and a static file per weight would be four downloads instead of one.
    expect(adminPanelFor(AdminFont::ROBOTO))
        ->toContain('font-weight: 100 900;', escape: false)
        ->toContain('font-display: swap;', escape: false);
});

it('ships the Roboto files it declares', function () {
    // A @font-face pointing at a missing woff2 does not throw — it 404s, and the
    // panel quietly renders in the fallback. So the binary is asserted here
    // rather than left for someone to notice by eye, and the signature is
    // checked so a truncated or HTML-error-response file is caught too.
    foreach (['roboto-latin.woff2', 'roboto-latin-ext.woff2'] as $file) {
        $path = public_path("fonts/{$file}");

        expect(is_file($path))->toBeTrue("public/fonts/{$file} is missing");
        expect(file_get_contents($path, false, null, 0, 4))->toBe('wOF2');
    }
});

it('describes no file for the system options', function () {
    foreach ([AdminFont::SYSTEM, AdminFont::SEGOE, 'a-face-that-does-not-exist'] as $value) {
        expect(AdminFont::facesFor($value))->toBe([])
            ->and(AdminFont::preloadFor($value))->toBeNull();
    }
});

it('leaves the storefront alone', function () {
    // Admin-only, in both directions. The panel's typeface is set here and read
    // nowhere else, and the storefront declares no Roboto of its own, so
    // changing it cannot restyle the public site.
    Setting::set('admin_font', 'roboto');

    $home = $this->get('/')->assertOk();

    $home->assertDontSee('/fonts/roboto-latin.woff2', escape: false)
        ->assertDontSee('Roboto\', ', escape: false);
});
