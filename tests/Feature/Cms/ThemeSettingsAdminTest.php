<?php

use App\Livewire\Admin\ThemeSettings\Index as ThemeSettings;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\User;
use App\Support\Themes;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

afterEach(function () {
    // Never leave a folder the install tests created on the real filesystem.
    File::deleteDirectory(Themes::path().'/retro');
    File::deleteDirectory(Themes::path().'/foo');
    File::deleteDirectory(Themes::path().'/bar');
    File::deleteDirectory(Themes::path().'/my-cool-store');
});

/**
 * Builds a real zip whose entries live under a single top-level folder ($slug),
 * mirroring what the installer expects. Returns the zip's file path.
 *
 * @param  array<string, string>  $files
 */
function makeThemeZip(string $slug, array $files): string
{
    $path = tempnam(sys_get_temp_dir(), 'theme-zip-').'.zip';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($files as $file => $content) {
        $zip->addFromString($slug.'/'.$file, $content);
    }

    $zip->close();

    return $path;
}

it('renders the theme settings page', function () {
    Livewire::test(ThemeSettings::class)
        ->assertStatus(200)
        ->assertSee('Enable Live Chat')
        ->assertSee('Show Announcement Popup');
});

it('links the theme builder guide PDF in the install modal', function () {
    expect(is_file(public_path('docs/theme-builder-guide.pdf')))->toBeTrue();

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->assertSet('showInstallModal', true)
        ->assertSeeHtml('docs/theme-builder-guide.pdf');
});

it('supplies each theme manifest (name, version, author, tags) to the picker', function () {
    Livewire::test(ThemeSettings::class)
        ->assertViewHas('themeCards', function (array $cards): bool {
            $ecommerce = $cards['ecommerce'];

            return $ecommerce['manifest']['name'] === 'Ecommerce'
                && $ecommerce['manifest']['version'] === '1.0.0'
                && $ecommerce['manifest']['author'] === 'Codeware'
                && is_array($ecommerce['manifest']['tags']);
        });
});

it('falls back to slug-derived manifest fields when a theme has no theme.json', function () {
    // 'default' ships a theme.json; but Themes::manifest() with a slug pointing
    // at a folder without one (e.g. the freshly installed test theme in the
    // other tests) must still yield a well-formed manifest.
    $zip = makeThemeZip('retro', ['home.blade.php' => 'retro home']);

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(Themes::manifest('retro'))
        ->name->toBe('Retro')
        ->version->toBe('1.0.0')
        ->author->toBe('')
        ->tags->toBe([]);
});

it('treats a malformed theme.json as if it were missing', function () {
    $zip = makeThemeZip('retro', [
        'home.blade.php' => 'retro home',
        'theme.json' => '{ not valid json ;;',
    ]);

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(Themes::manifest('retro'))
        ->name->toBe('Retro')
        ->version->toBe('1.0.0');
});

it('renders the selected theme settings panel below the picker', function () {
    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertSeeHtml('Ecommerce')
        ->assertSeeHtml('v1.0.0');
});

it('loads theme-scoped settings stored under the theme_ prefix', function () {
    Setting::factory()->create(['key' => 'theme_ecommerce_hero_badge', 'value' => 'New season', 'group' => 'theme', 'type' => 'string']);

    $component = Livewire::test(ThemeSettings::class);

    expect($component->get('settings.theme_ecommerce_hero_badge'))->toBe('New season');
});

it('saves theme-scoped settings through Setting::set', function () {
    Livewire::test(ThemeSettings::class)
        ->set('settings.theme_ecommerce_hero_badge', 'Summer sale')
        ->set('settings.theme_portfolio_hero_title', 'Designer')
        ->call('save');

    expect(Setting::where('key', 'theme_ecommerce_hero_badge')->value('value'))->toBe('Summer sale')
        ->and(Setting::where('key', 'theme_portfolio_hero_title')->value('value'))->toBe('Designer');
});

it('renders the ecommerce theme color pickers', function () {
    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertSee('Primary Color')
        ->assertSee('Secondary Color')
        ->assertSeeHtml('theme_ecommerce_primary_color')
        ->assertSeeHtml('theme_ecommerce_secondary_color')
        ->assertSeeHtml('type="color"');
});

it('saves the ecommerce theme primary & secondary colors through Setting::set', function () {
    Livewire::test(ThemeSettings::class)
        ->set('settings.theme_ecommerce_primary_color', '#c01616')
        ->set('settings.theme_ecommerce_secondary_color', '#1e7bc4')
        ->call('save');

    expect(Setting::where('key', 'theme_ecommerce_primary_color')->value('value'))->toBe('#c01616')
        ->and(Setting::where('key', 'theme_ecommerce_secondary_color')->value('value'))->toBe('#1e7bc4');
});

it('loads seeded ecommerce theme colors into the form', function () {
    Setting::factory()->create(['key' => 'theme_ecommerce_primary_color', 'value' => '#045b30', 'group' => 'frontend', 'type' => 'color']);
    Setting::factory()->create(['key' => 'theme_ecommerce_secondary_color', 'value' => '#7cc242', 'group' => 'frontend', 'type' => 'color']);

    $component = Livewire::test(ThemeSettings::class);

    expect($component->get('settings.theme_ecommerce_primary_color'))->toBe('#045b30')
        ->and($component->get('settings.theme_ecommerce_secondary_color'))->toBe('#7cc242');
});

it('renders the selected theme settings blade when the theme ships one', function () {
    expect(Themes::hasSettings('ecommerce'))->toBeTrue()
        ->and(Themes::hasSettings('default'))->toBeTrue()
        ->and(Themes::hasSettings('portfolio'))->toBeTrue();

    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertSee('Hero Badge')
        ->assertSee('theme_ecommerce_hero_badge')
        ->assertSee('Promo Banner 1 Title');
});

it('shows the theme settings guide via the info icon on the theme settings card', function () {
    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'ecommerce')
        ->assertSee('How theme settings work')
        ->assertSee('Theme Settings Guide')
        ->assertSee('settings.blade.php')
        ->assertSee('Theme Builder Guide PDF')
        ->assertSee('Setting::get()');
});

it('omits the theme settings card when the selected theme has none', function () {
    $zip = makeThemeZip('retro', ['home.blade.php' => 'retro home']);

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(Themes::hasSettings('retro'))->toBeFalse();

    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'retro')
        ->assertDontSee('Theme Settings</h')
        ->assertDontSee('theme_retro_');
});

it('is reachable by admins via its own route', function () {
    $this->get(route('admin.theme-settings'))->assertOk();
});

it('loads existing theme settings into the form', function () {
    Setting::factory()->create(['key' => 'site_theme', 'value' => 'ecommerce', 'group' => 'frontend', 'type' => 'select']);
    Setting::factory()->create(['key' => 'site_tagline', 'value' => 'Shop smart', 'group' => 'frontend', 'type' => 'textarea']);
    Setting::factory()->create(['key' => 'chat_widget_enabled', 'value' => '0', 'group' => 'frontend', 'type' => 'boolean']);
    Setting::factory()->create(['key' => 'popup_enabled', 'value' => '1', 'group' => 'frontend', 'type' => 'boolean']);
    Setting::factory()->create(['key' => 'popup_title', 'value' => 'Welcome', 'group' => 'frontend', 'type' => 'string']);

    $component = Livewire::test(ThemeSettings::class);

    expect($component->get('settings.site_theme'))->toBe('ecommerce')
        ->and($component->get('settings.site_tagline'))->toBe('Shop smart')
        ->and($component->get('settings.chat_widget_enabled'))->toBe(false)
        ->and($component->get('settings.popup_enabled'))->toBe(true)
        ->and($component->get('settings.popup_title'))->toBe('Welcome');
});

it('lists every installed theme folder as a selectable design', function () {
    Livewire::test(ThemeSettings::class)
        ->assertViewHas('themes', fn ($themes) => collect(['default', 'ecommerce', 'portfolio'])->diff(array_keys($themes))->isEmpty());
});

it('saves theme settings through Setting::set', function () {
    Setting::factory()->create(['key' => 'site_theme', 'value' => 'default', 'group' => 'frontend', 'type' => 'select']);
    Setting::factory()->create(['key' => 'home_hero_image', 'value' => '', 'group' => 'frontend', 'type' => 'string']);
    Setting::factory()->create(['key' => 'chat_widget_enabled', 'value' => '1', 'group' => 'frontend', 'type' => 'boolean']);
    Setting::factory()->create(['key' => 'popup_enabled', 'value' => '0', 'group' => 'frontend', 'type' => 'boolean']);

    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'ecommerce')
        ->set('settings.home_hero_image', 'media/hero.jpg')
        ->set('settings.chat_widget_enabled', false)
        ->set('settings.popup_enabled', true)
        ->set('settings.popup_title', 'Welcome to our store')
        ->set('settings.popup_description', 'Get 10% off.')
        ->set('settings.popup_button_label', 'Shop Now')
        ->set('settings.popup_button_url', '/shop')
        ->call('save');

    expect(Setting::where('key', 'site_theme')->value('value'))->toBe('ecommerce')
        ->and(Setting::where('key', 'home_hero_image')->value('value'))->toBe('media/hero.jpg')
        ->and(Setting::where('key', 'chat_widget_enabled')->value('value'))->toBe('0')
        ->and(Setting::where('key', 'popup_enabled')->value('value'))->toBe('1')
        ->and(Setting::where('key', 'popup_title')->value('value'))->toBe('Welcome to our store')
        ->and(Setting::where('key', 'popup_description')->value('value'))->toBe('Get 10% off.')
        ->and(Setting::where('key', 'popup_button_label')->value('value'))->toBe('Shop Now')
        ->and(Setting::where('key', 'popup_button_url')->value('value'))->toBe('/shop');
});

it('registers a Theme Settings item under Library & System in the admin menu', function () {
    $this->seed(AdminMenuSeeder::class);

    $item = MenuItem::where('group', MenuItem::GROUP_ADMIN_SIDEBAR)
        ->where('route_name', 'admin.theme-settings')
        ->first();

    expect($item)->not->toBeNull()
        ->and($item->label)->toBe('Theme Settings')
        ->and($item->icon)->toBe('swatch');
});

it('opens the install theme modal', function () {
    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->assertSet('showInstallModal', true)
        ->call('closeInstallModal')
        ->assertSet('showInstallModal', false);
});

it('installs a theme from a zip into the themes directory', function () {
    $zip = makeThemeZip('retro', [
        'home.blade.php' => 'retro home',
        'page.blade.php' => 'retro page',
        'partials/head.blade.php' => 'retro head',
    ]);

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('retro.zip', file_get_contents($zip)))
        ->call('installTheme')
        ->assertSet('showInstallModal', false)
        ->assertHasNoErrors();

    expect(is_dir(Themes::path().'/retro'))->toBeTrue()
        ->and(file_exists(Themes::path().'/retro/home.blade.php'))->toBeTrue()
        ->and(file_exists(Themes::path().'/retro/partials/head.blade.php'))->toBeTrue()
        ->and(Themes::all())->toHaveKey('retro');
});

it('turns a spaced theme folder name into a slugged theme folder', function () {
    $zip = makeThemeZip('My Cool Store', [
        'home.blade.php' => 'cool home',
    ]);

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('my-cool-store.zip', file_get_contents($zip)))
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(is_dir(Themes::path().'/my-cool-store'))->toBeTrue();
});

it('rejects a file that is not a valid zip theme package', function () {
    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->create('theme.zip', 256))
        ->call('installTheme')
        ->assertHasErrors(['themeZip']);
});

it('rejects a zip that does not contain exactly one root theme folder', function () {
    $zip = makeThemeZip('foo', ['home.blade.php' => 'foo home']);
    $zipBar = makeThemeZip('bar', ['home.blade.php' => 'bar home']);

    $merged = tempnam(sys_get_temp_dir(), 'theme-zip-').'.zip';
    copy($zip, $merged);

    $wrap = new ZipArchive;
    $wrap->open($merged, ZipArchive::CREATE);
    foreach (['bar/home.blade.php' => 'bar home'] as $file => $content) {
        $wrap->addFromString($file, $content);
    }
    $wrap->close();

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('two-themes.zip', file_get_contents($merged)))
        ->call('installTheme')
        ->assertHasErrors(['themeZip']);
});

it('does not overwrite an already-installed theme', function () {
    $zip = makeThemeZip('default', ['home.blade.php' => 'evil home']);

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('default.zip', file_get_contents($zip)))
        ->call('installTheme')
        ->assertHasErrors(['themeZip']);
});

it('rejects a zip containing path-traversal entries', function () {
    $path = tempnam(sys_get_temp_dir(), 'theme-zip-').'.zip';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('retro/home.blade.php', 'fine');
    $zip->addFromString('retro/../../evil.txt', 'pwn');
    $zip->close();

    Livewire::test(ThemeSettings::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('bad.zip', file_get_contents($path)))
        ->call('installTheme')
        ->assertHasErrors(['themeZip']);

    expect(is_dir(Themes::path().'/retro'))->toBeFalse();
});
