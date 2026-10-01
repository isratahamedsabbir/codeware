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
| The default is the system font. Every other option is a sub-folder of
| public/fonts/ — drop a folder in and it is offered; select it and the layout
| declares its @font-face and puts it ahead of the system stack.
|
*/

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
    $this->tmpFont = public_path('fonts/zz-test-font');
});

afterEach(function () {
    File::deleteDirectory($this->tmpFont);
});

function adminPanelFor(string $value): string
{
    Setting::set('admin_font', $value);

    return view('layouts.admin', [
        'title' => 'Settings',
        'slot' => new ComponentSlot('<p>panel</p>'),
    ])->render();
}

it('offers the system font first, then every font folder', function () {
    $options = AdminFont::options();

    expect(array_key_first($options))->toBe('system')
        ->and($options)->toHaveKey('roboto', 'Roboto');
});

it('picks up a new folder with no code change', function () {
    File::ensureDirectoryExists($this->tmpFont);
    File::put($this->tmpFont.'/ZzTest-Bold.woff2', 'wOF2');
    File::put($this->tmpFont.'/ZzTest-Italic.ttf', 'x');

    expect(AdminFont::options())->toHaveKey('zz-test-font', 'Zz Test Font')
        ->and(AdminFont::stackFor('zz-test-font'))->toStartWith("'zz-test-font', ui-sans-serif");

    $faces = AdminFont::facesFor('zz-test-font');
    expect($faces)->toHaveCount(2)
        ->and($faces[0]['weight'])->toBe('700')
        ->and($faces[0]['format'])->toBe('woff2')
        ->and($faces[1]['style'])->toBe('italic')
        ->and($faces[1]['format'])->toBe('truetype');

    expect(adminPanelFor('zz-test-font'))
        ->toContain("font-family: 'zz-test-font';", escape: false)
        ->toContain("src: url('/fonts/zz-test-font/ZzTest-Bold.woff2') format('woff2');", escape: false);
});

it('ignores folders with no font files and files outside a folder', function () {
    File::ensureDirectoryExists($this->tmpFont);
    File::put($this->tmpFont.'/readme.txt', 'x');

    expect(AdminFont::options())->not->toHaveKey('zz-test-font')
        ->and(AdminFont::options())->not->toHaveKey('roboto-latin.woff2');
});

it('falls back to the system font, with no error, when the saved font folder was deleted', function () {
    File::ensureDirectoryExists($this->tmpFont);
    File::put($this->tmpFont.'/ZzTest-Regular.woff2', 'wOF2');
    Setting::set('admin_font', 'zz-test-font');
    File::deleteDirectory($this->tmpFont);

    expect(adminPanelFor('zz-test-font'))
        ->toContain('--font-sans: '.AdminFont::stacks()['system'], escape: false)
        ->not->toContain('@font-face', escape: false);

    expect(Livewire::test(SettingsIndex::class)->get('settings.admin_font'))->toBe('system');
});

it('defaults to letting the OS answer', function () {
    expect(AdminFont::stackFor(null))->toBe(AdminFont::stacks()['system'])
        ->and(AdminFont::stackFor(''))->toBe(AdminFont::stacks()['system']);
});

it('ends every stack in a generic family so text always renders', function () {
    foreach (AdminFont::stacks() as $stack) {
        expect($stack)->toEndWith('sans-serif');
    }
});

it('falls back to the system font for a value that is not an option', function () {
    expect(AdminFont::normalize('a-face-that-does-not-exist'))->toBe('system')
        ->and(AdminFont::normalize('../etc'))->toBe('system')
        ->and(AdminFont::normalize(['not', 'a', 'string']))->toBe('system')
        ->and(AdminFont::normalize('roboto'))->toBe('roboto');
});

it('renders the choice on the settings page and saves it', function () {
    Livewire::test(SettingsIndex::class)
        ->assertSee('Backend Font')
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

    expect(Setting::get('admin_font'))->toBe('system');
});

it('defaults the field to the system font on a database that predates the setting', function () {
    expect(Livewire::test(SettingsIndex::class)->get('settings.admin_font'))->toBe('system');
});

it('downloads nothing for the system font', function () {
    expect(adminPanelFor('system'))
        ->not->toContain('as="font"', escape: false)
        ->not->toContain('@font-face', escape: false);

    expect(AdminFont::facesFor('system'))->toBe([])
        ->and(AdminFont::preloadFor('system'))->toBeNull();
});

it('preloads the latin file and declares both Roboto subsets with their ranges', function () {
    $panel = adminPanelFor('roboto');

    expect($panel)
        ->toContain('<link rel="preload" href="/fonts/roboto/roboto-latin.woff2" as="font" type="font/woff2" crossorigin>', escape: false)
        ->not->toContain('roboto-latin-ext.woff2" as="font"', escape: false)
        ->toContain("src: url('/fonts/roboto/roboto-latin-ext.woff2') format('woff2');", escape: false)
        ->toContain('unicode-range: U+0000-00FF,', escape: false)
        ->toContain('unicode-range: U+0100-02BA,', escape: false)
        ->toContain('font-weight: 100 900;', escape: false)
        ->toContain('font-display: swap;', escape: false);
});

it('ships valid woff2 files for Roboto', function () {
    foreach (['roboto-latin.woff2', 'roboto-latin-ext.woff2'] as $file) {
        $path = public_path("fonts/roboto/{$file}");

        expect(is_file($path))->toBeTrue("public/fonts/roboto/{$file} is missing");
        expect(file_get_contents($path, false, null, 0, 4))->toBe('wOF2');
    }
});

it('leaves the storefront alone', function () {
    Setting::set('admin_font', 'roboto');

    $this->get('/')->assertOk()->assertDontSee('/fonts/roboto/', escape: false);
});
