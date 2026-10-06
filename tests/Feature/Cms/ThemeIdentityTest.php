<?php

use App\Livewire\Admin\ThemeSettings\Index as ThemeSettingsScreen;
use App\Models\Setting;
use App\Models\User;
use App\Support\Themes;
use App\Support\ThemeSettings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * A theme's name and serial number are the two fields that tell one installed
 * theme from another, so both are unique across the installed set and both live
 * in the theme's own theme.json (see App\Support\Themes).
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

afterEach(function () {
    // Never leave a folder these tests created on the real filesystem.
    File::deleteDirectory(Themes::path().'/retro');
});

/**
 * A theme folder on disk with a manifest, so a test can act on an installed
 * theme without going near the zip installer for every case that is really only
 * about what the app does with a name once it has one.
 *
 * @param  array<string, mixed>  $manifest
 */
function installThemeWithManifest(string $slug, array $manifest): void
{
    $folder = Themes::path().'/'.$slug;

    File::ensureDirectoryExists($folder);
    File::put($folder.'/'.ThemeSettings::FILE, json_encode($manifest)."\n");

    Themes::forget();
    ThemeSettings::forget();
}

/**
 * A real zip whose entries live under a single top-level folder, which is what
 * the installer expects to find.
 *
 * @param  array<string, string>  $files
 */
function makeIdentityThemeZip(string $folder, array $files): string
{
    $path = tempnam(sys_get_temp_dir(), 'theme-identity-').'.zip';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($files as $file => $content) {
        $zip->addFromString($folder.'/'.$file, $content);
    }

    $zip->close();

    return $path;
}

/**
 * Upload a packaged theme through the installer's own screen.
 */
function installPackagedTheme(string $path): Testable
{
    return Livewire::test(ThemeSettingsScreen::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('theme.zip', file_get_contents($path)))
        ->set('installPassword', 'password')
        ->call('installTheme');
}

it('gives every bundled theme a serial number of its own', function () {
    $sn = collect(array_keys(Themes::all()))->mapWithKeys(fn (string $slug) => [$slug => Themes::manifest($slug)['sn']]);

    expect($sn)->not->toContain(0, 'a bundled theme shipped without a serial number');
    expect($sn->unique())->toHaveCount($sn->count(), 'two bundled themes share a serial number');
});

it('counts a theme with no serial number as none, not as zero', function () {
    installThemeWithManifest('retro', ['name' => 'Retro']);

    expect(Themes::manifest('retro')['sn'])->toBe(0);
});

it('hands out the next free serial number rather than a count of themes', function () {
    installThemeWithManifest('retro', ['name' => 'Retro', 'sn' => 7]);

    expect(Themes::nextSn())->toBe(8);
});

it('never reissues the number of a theme that has been deleted', function () {
    // SNs 1-3 are the bundled themes. A site that deleted its #2 must not get
    // #2 handed to the next install — that number referred to a real theme once.
    expect(Themes::nextSn())->toBe(4);

    installThemeWithManifest('retro', ['name' => 'Retro', 'sn' => 12]);

    expect(Themes::nextSn())->toBe(13);
});

it('reads a hand-typed serial number in any of the shapes a json file can hold', function () {
    installThemeWithManifest('retro', ['name' => 'Retro', 'sn' => '9']);

    expect(Themes::manifest('retro')['sn'])->toBe(9);
});

it('treats a serial number that is not a number as no serial number', function () {
    installThemeWithManifest('retro', ['name' => 'Retro', 'sn' => 'first']);

    expect(Themes::manifest('retro')['sn'])->toBe(0);
});

it('refuses a theme name another installed theme already answers to', function () {
    installThemeWithManifest('retro', ['name' => 'Retro Shop']);

    expect(Themes::isNameUnique('Retro Shop'))->toBeFalse();
    expect(Themes::themeWithName('Retro Shop'))->toBe('retro');
});

it('compares theme names the way the panel shows them', function () {
    // "Shop" and "shop" are the same word to whoever reads the picker, so the
    // two together are a duplicate — not two themes that differ by a capital.
    installThemeWithManifest('retro', ['name' => 'Shop']);

    expect(Themes::isNameUnique('shop'))->toBeFalse();
    expect(Themes::isNameUnique('  SHOP  '))->toBeFalse();
    expect(Themes::isNameUnique('Shop Plus'))->toBeTrue();
});

it('lets a theme keep the name it already has', function () {
    expect(Themes::isNameUnique('Ecommerce', 'ecommerce'))->toBeTrue();
    expect(Themes::isNameUnique('Portfolio', 'ecommerce'))->toBeFalse();
});

it('catches two slugs whose fallback names would collide', function () {
    // Neither name was ever typed by anybody: both fall back to the slug in title
    // case, and "my-shop" and "my_shop" are the same words.
    installThemeWithManifest('retro', []);

    expect(Themes::manifest('retro')['name'])->toBe('Retro');
    expect(Themes::isNameUnique('Retro'))->toBeFalse();
});

it('refuses to reuse another theme\'s serial number', function () {
    expect(Themes::isSnUnique(1))->toBeFalse();
    expect(Themes::slugBySn(2))->toBe('ecommerce');
    expect(Themes::isSnUnique(4))->toBeTrue();
    expect(Themes::isSnUnique(2, 'ecommerce'))->toBeTrue();
});

it('never hands out zero as a serial number', function () {
    // 0 is what a theme with no serial number reads back as, so it is not a
    // number anybody can be given.
    expect(Themes::isSnUnique(0))->toBeFalse();
});

it('gives a newly created theme file a serial number', function () {
    // create() describes a theme that is installed but has no file yet, so the
    // folder is what the test has to put there first.
    File::ensureDirectoryExists(Themes::path().'/retro');
    Themes::forget();

    $sn = Themes::nextSn();

    expect(ThemeSettings::create('retro', []))->toBeTrue();

    expect(Themes::manifest('retro')['sn'])->toBe($sn);
});

it('leaves a serial number the caller chose in place', function () {
    File::ensureDirectoryExists(Themes::path().'/retro');
    Themes::forget();

    ThemeSettings::create('retro', ['sn' => 40]);

    expect(Themes::manifest('retro')['sn'])->toBe(40);
});

it('shows the theme name read-only, with no identity form', function () {
    Setting::set('site_theme', 'ecommerce');

    Livewire::test(ThemeSettingsScreen::class)
        ->assertSee('Ecommerce')
        ->assertDontSee('Save identity');
});

it('gives an installed theme the next free serial number', function () {
    $zip = makeIdentityThemeZip('retro', ['home.blade.php' => 'retro home']);

    $expected = Themes::nextSn();

    installPackagedTheme($zip)->assertHasNoErrors();

    expect(Themes::manifest('retro')['sn'])->toBe($expected);
});

it('gives an installed theme that shipped its own manifest file a number too', function () {
    $zip = makeIdentityThemeZip('retro', [
        'home.blade.php' => 'retro home',
        ThemeSettings::FILE => json_encode(['name' => 'Retro Classic', 'version' => '2.1.0']),
    ]);

    installPackagedTheme($zip)->assertHasNoErrors();

    expect(Themes::manifest('retro')['sn'])->toBe(Themes::nextSn() - 1)
        ->and(Themes::manifest('retro')['name'])->toBe('Retro Classic')
        ->and(Themes::manifest('retro')['version'])->toBe('2.1.0');
});

it('keeps the serial number a package shipped with', function () {
    // A theme zip that has been round this loop before comes back with the number
    // the rest of the site has been calling it by, and handing out a new one
    // would silently renumber it.
    $zip = makeIdentityThemeZip('retro', [
        'home.blade.php' => 'retro home',
        ThemeSettings::FILE => json_encode(['name' => 'Retro Classic', 'sn' => 25]),
    ]);

    installPackagedTheme($zip)->assertHasNoErrors();

    expect(Themes::manifest('retro')['sn'])->toBe(25);
});

it('refuses a package whose theme name is already taken', function () {
    $zip = makeIdentityThemeZip('retro', [
        'home.blade.php' => 'retro home',
        ThemeSettings::FILE => json_encode(['name' => 'Ecommerce']),
    ]);

    installPackagedTheme($zip)->assertHasErrors('themeZip');

    expect(File::isDirectory(Themes::path().'/retro'))->toBeFalse();
});

it('refuses a package whose slug would derive a name that is already taken', function () {
    // "my-shop" and "my_shop" are the same words in title case, so the second
    // package collides with the first without anybody having typed a name.
    installThemeWithManifest('retro', ['name' => 'My Shop']);

    $zip = makeIdentityThemeZip('my_shop', ['home.blade.php' => 'shop home']);

    installPackagedTheme($zip)->assertHasErrors('themeZip');

    expect(File::isDirectory(Themes::path().'/my_shop'))->toBeFalse();
});
