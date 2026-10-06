<?php

use App\Livewire\Admin\ThemeSettings\Index;
use App\Models\Setting;
use App\Models\User;
use App\Support\Plugins;
use App\Support\Themes;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/**
 * Deleting a theme or a plugin is the one admin action that removes code from the
 * running project rather than a row from a table, so every other thing still
 * pointing at the slug — the storefront, the admin layout, the sidebar, the theme
 * asset route, the plugin's own URL — has to survive it. These walk a real folder
 * through install → activate → delete and then touch each of those surfaces,
 * because the failure is a 500 on some *later* page load, never anything the
 * delete call itself reports.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
});

afterEach(function () {
    File::deleteDirectory(Themes::path().'/doomed');
    File::deleteDirectory(Plugins::path().'/doomed-plugin');
    Plugins::flush();
    Themes::forget();
});

function doomedThemeFiles(): array
{
    return [
        'home.blade.php' => 'doomed home',
        'routes/web.php' => '<?php // doomed routes',
        'theme.json' => json_encode(['name' => 'Doomed Theme', 'version' => '1.0.0', 'sn' => 99]),
        'public/css/theme.css' => '@import "../../../../resources/css/base.css";',
        // A hand-written static file, because css/theme.css is deliberately not
        // servable: it is a Tailwind source, not an asset.
        'public/img/logo.svg' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
    ];
}

function zipFor(string $slug, array $files): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'mod-zip-').'.zip';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($files as $file => $content) {
        $zip->addFromString($slug.'/'.$file, $content);
    }

    $zip->close();

    return UploadedFile::fake()->createWithContent($slug.'.zip', file_get_contents($path));
}

function installDoomedTheme(): void
{
    Livewire::test(Index::class)
        ->call('openInstallModal')
        ->set('themeZip', zipFor('doomed', doomedThemeFiles()))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasNoErrors();

    expect(is_dir(Themes::path().'/doomed'))->toBeTrue();
}

function installDoomedPlugin(array $extraFiles = []): void
{
    Plugins::create([
        'name' => 'Doomed Plugin',
        'slug' => 'doomed-plugin',
        'version' => '1.0.0',
        'description' => 'About to be deleted',
        'author' => 'Tests',
        'icon' => 'puzzle-piece',
    ]);

    foreach ($extraFiles as $relative => $contents) {
        $path = Plugins::path().'/doomed-plugin/'.$relative;
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    Plugins::flush();
}

it('leaves the storefront and the admin working after a theme is deleted', function () {
    installDoomedTheme();

    expect(array_key_exists('doomed', Themes::all()))->toBeTrue()
        ->and(Themes::delete('doomed'))->toBeTrue()
        ->and(is_dir(Themes::path().'/doomed'))->toBeFalse();

    expect(array_key_exists('doomed', Themes::all()))->toBeFalse();

    $this->get('/')->assertOk();
    $this->get(route('admin.theme-settings'))->assertOk();
    $this->get(route('admin.dashboard'))->assertOk();
});

it('falls back to a live theme when the deleted theme was the one picked in Site Design', function () {
    installDoomedTheme();

    // The pick, then the deletion: a delete is refused while a theme is live, so
    // moving off it first is the only order in which this state is reachable.
    Setting::set('site_theme', 'doomed');
    expect(Themes::active())->toBe('doomed');

    Setting::set('site_theme', 'default');
    Themes::delete('doomed');
    Themes::forget();

    // The stale pick must not survive into the screens that read it.
    Setting::set('site_theme', 'doomed');

    expect(Themes::active())->toBe('default');

    $this->get('/')->assertOk();
    $this->get(route('admin.theme-settings'))->assertOk();
});

it('refuses to delete the live theme, so the storefront can never lose its own templates', function () {
    installDoomedTheme();
    Setting::set('site_theme', 'doomed');

    expect(Themes::delete('doomed'))->toBeFalse()
        ->and(is_dir(Themes::path().'/doomed'))->toBeTrue()
        ->and(Themes::undeletableBecause('doomed'))->toContain('live');

    $this->get('/')->assertOk();
});

it('404s a deleted theme\'s asset route instead of erroring', function () {
    installDoomedTheme();

    $this->get('/themes/doomed/img/logo.svg')->assertOk();

    Themes::delete('doomed');
    Themes::forget();

    $this->get('/themes/doomed/img/logo.svg')->assertNotFound();
    $this->get('/themes/doomed/css/theme.css')->assertNotFound();
});

it('reinstalls a deleted theme cleanly, with no settings carried over', function () {
    installDoomedTheme();

    $zip = Themes::toZip('doomed');
    Themes::delete('doomed');
    Themes::forget();

    Livewire::test(Index::class)
        ->call('openInstallModal')
        ->set('themeZip', UploadedFile::fake()->createWithContent('doomed.zip', file_get_contents($zip)))
        ->set('installPassword', 'password')
        ->call('installTheme')
        ->assertHasNoErrors();

    @unlink($zip);
    Themes::forget();

    expect(Themes::isInstalled('doomed'))->toBeTrue();

    $this->get('/')->assertOk();
    $this->get(route('admin.theme-settings'))->assertOk();
});

it('leaves the admin working after a plugin with a header widget is deleted', function () {
    installDoomedPlugin([
        'header.blade.php' => '<div id="doomed-widget">doomed widget</div>',
    ]);

    Plugins::setActive('doomed-plugin', true);
    Plugins::saveSettings('doomed-plugin', ['enabled' => false]);

    expect(Plugins::find('doomed-plugin')['active'])->toBeTrue();

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('doomed-widget');

    expect(Plugins::delete('doomed-plugin'))->toBeTrue()
        ->and(is_dir(Plugins::path().'/doomed-plugin'))->toBeFalse()
        ->and(Plugins::find('doomed-plugin'))->toBeNull();

    // Every surface that used to know about the plugin.
    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('doomed-widget');
    $this->get(route('admin.plugin-settings'))->assertOk();
    $this->get(route('admin.plugins.show', 'doomed-plugin'))->assertNotFound();
    $this->get('/plugins/doomed-plugin/anything')->assertNotFound();
    $this->get(route('admin.developer-guide'))->assertOk();
});

it('drops a deleted plugin out of the sidebar dropdown', function () {
    installDoomedPlugin();
    Plugins::setActive('doomed-plugin', true);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Doomed Plugin');

    Plugins::delete('doomed-plugin');

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Doomed Plugin');
});

it('leaves no trace of a deleted plugin in settings', function () {
    installDoomedPlugin();
    Plugins::setActive('doomed-plugin', true);
    Plugins::saveSettings('doomed-plugin', ['enabled' => false]);

    expect(Setting::get('plugin_doomed-plugin_settings'))->not->toBe('[]');

    Plugins::delete('doomed-plugin');

    // Nothing keyed to a folder that no longer exists: an orphaned settings row
    // would quietly resurrect itself if the same slug were ever installed again.
    expect(Setting::get('plugin_doomed-plugin_settings', null))->toBeNull()
        ->and(json_decode((string) Setting::get('plugins_active', '[]'), true) ?: [])
        ->not->toContain('doomed-plugin');
});

it('reinstalling a deleted plugin does not inherit its old settings', function () {
    installDoomedPlugin();
    Plugins::setActive('doomed-plugin', true);
    Plugins::saveSettings('doomed-plugin', ['enabled' => false]);

    $zip = Plugins::toZip('doomed-plugin');
    Plugins::delete('doomed-plugin');

    expect(Plugins::installFromZip($zip))->toBe('doomed-plugin');

    @unlink($zip);
    Plugins::flush();

    // Back to the manifest's own default, not the deleted install's value.
    expect(Plugins::settings('doomed-plugin'))->toBe(['enabled' => true]);

    $this->get(route('admin.plugin-settings'))->assertOk();
});

it('refuses to delete a theme or plugin that is not installed', function () {
    expect(Themes::delete('does-not-exist'))->toBeFalse()
        ->and(Themes::delete('../storage'))->toBeFalse()
        ->and(Plugins::delete('does-not-exist'))->toBeFalse()
        ->and(Plugins::delete('clock'))->toBeFalse();

    expect(is_dir(base_path('storage')))->toBeTrue();
});

it('refuses to delete the bundled default theme', function () {
    expect(Themes::delete('default'))->toBeFalse()
        ->and(Themes::undeletableBecause('default'))->not->toBeNull()
        ->and(is_dir(Themes::path().'/default'))->toBeTrue();
});
