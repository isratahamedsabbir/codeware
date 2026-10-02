<?php

use App\Livewire\Admin\Plugins\Index;
use App\Livewire\Admin\Plugins\Show;
use App\Models\MenuItem;
use App\Models\User;
use App\Support\Plugins;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    Plugins::flush();
    // Whatever the test adds under plugins/ is removed again afterwards.
    $this->slugsBefore = array_keys(Plugins::all());
});

afterEach(function () {
    File::deleteDirectory(Plugins::path().'/demo-plugin');

    foreach (array_diff(array_keys(Plugins::all()), $this->slugsBefore) as $slug) {
        File::deleteDirectory(Plugins::path().'/'.$slug);
    }

    Plugins::flush();
});

function demoPluginZip(bool $withIndex = true): Illuminate\Http\Testing\File
{
    $path = tempnam(sys_get_temp_dir(), 'plg').'.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);
    $zip->addFromString('demo-plugin/plugin.json', json_encode(['name' => 'Demo Plugin', 'version' => '2.0.0']));

    if ($withIndex) {
        $zip->addFromString('demo-plugin/index.blade.php', '<div>demo plugin screen</div>');
    }

    $zip->close();

    return UploadedFile::fake()->createWithContent('demo-plugin.zip', file_get_contents($path));
}

it('ships the Clock default plugin that is always active', function () {
    $default = Plugins::find('clock');

    expect($default)->not->toBeNull()
        ->and($default['default'])->toBeTrue()
        ->and($default['active'])->toBeTrue();
});

it('lists the default plugin and Plugin Settings in the sidebar Plugins dropdown', function () {
    $this->actingAs($this->admin);

    $group = MenuItem::menuForCurrentUser()->first(fn ($i) => $i->is_group && $i->label === 'Plugins');

    expect($group)->not->toBeNull()
        ->and($group->children->pluck('label')->all())->toBe(['Clock', 'Plugin Settings']);
});

it('places the Plugins dropdown directly before About, which stays last', function () {
    $this->seed(AdminMenuSeeder::class);
    $this->actingAs($this->admin);

    $labels = MenuItem::menuForCurrentUser()
        ->map(fn (MenuItem $item) => $item->label)
        ->all();

    expect(array_search('Plugins', $labels, true))
        ->toBe(array_search('About', $labels, true) - 1)
        ->and(end($labels))->toBe('About');
});

it('renders a plugin index view inside the admin layout', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.plugins.show', 'clock'))
        ->assertOk()
        ->assertSee('Clock');
});

it('404s for unknown or inactive plugins', function () {
    $this->actingAs($this->admin)->get(route('admin.plugins.show', 'nope'))->assertNotFound();
});

it('keeps plugin screens away from staff', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.plugin-settings'))
        ->assertForbidden();
});

it('installs, activates, deactivates and removes a plugin from Plugin Settings', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)
        ->set('pluginZip', demoPluginZip())
        ->call('installPlugin')
        ->assertHasNoErrors();

    expect(Plugins::find('demo-plugin')['active'])->toBeFalse();

    Livewire::test(Index::class)->call('toggle', 'demo-plugin');
    expect(Plugins::isValidSlug('demo-plugin'))->toBeTrue()
        ->and(Plugins::find('demo-plugin')['active'])->toBeTrue();

    $this->get(route('admin.plugins.show', 'demo-plugin'))->assertOk()->assertSee('demo plugin screen');

    Livewire::test(Index::class)->call('toggle', 'demo-plugin');
    expect(Plugins::find('demo-plugin')['active'])->toBeFalse();

    Livewire::test(Index::class)->call('remove', 'demo-plugin');
    expect(Plugins::find('demo-plugin'))->toBeNull();
});

it('rejects a package without an index.blade.php', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)
        ->set('pluginZip', demoPluginZip(withIndex: false))
        ->call('installPlugin')
        ->assertHasErrors('pluginZip');

    expect(Plugins::find('demo-plugin'))->toBeNull();
});

it('never deactivates or deletes the default plugin', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)->call('toggle', 'clock')->call('remove', 'clock');

    expect(Plugins::find('clock')['active'])->toBeTrue();
});

it('shows the clock icon in the header only once the clock is enabled', function () {
    $this->actingAs($this->admin);

    $this->get(route('admin.dashboard'))->assertDontSee('aria-label="Clock"', false);

    Livewire::test(Show::class, ['slug' => 'clock'])
        ->set('values.enabled', true);

    expect(Plugins::settings('clock')['enabled'])->toBeTrue();

    $this->get(route('admin.dashboard'))->assertSee('aria-label="Clock"', false);
});

it('ships a Calendar plugin that stays out of the header until activated', function () {
    $this->actingAs($this->admin);

    expect(Plugins::find('calendar')['active'])->toBeFalse();
    $this->get(route('admin.dashboard'))->assertDontSee('aria-label="Calendar"', false);

    Livewire::test(Index::class)->call('toggle', 'calendar');
    Plugins::setActive('calendar', false);
    Plugins::setActive('calendar', true);

    $this->get(route('admin.dashboard'))->assertSee('aria-label="Calendar"', false);
    $this->get(route('admin.plugins.show', 'calendar'))->assertOk()->assertSee('Week starts on');

    Plugins::setActive('calendar', false);
});

it('puts Plugin Settings right after Theme Settings, not in the Plugins dropdown', function () {
    $this->seed(AdminMenuSeeder::class);
    // Simulate an older menu that predates the seeded Plugin Settings row.
    MenuItem::where('route_name', 'admin.plugin-settings')->delete();
    $this->actingAs($this->admin);

    $menu = MenuItem::menuForCurrentUser();
    $library = $menu->first(fn ($i) => $i->is_group && $i->label === 'Library & System');
    $labels = $library->children->pluck('label')->all();

    expect($labels[array_search('Theme Settings', $labels) + 1])->toBe('Plugin Settings')
        ->and($menu->first(fn ($i) => $i->label === 'Plugins')->children->pluck('label')->all())->toBe(['Clock']);
});

it('renders the plugin guide button on Plugin Settings', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.plugin-settings'))
        ->assertOk()
        ->assertSee('How to build and use a plugin')
        ->assertSee('plugin.json');
});

it('creates a plugin folder from the basics given in the modal', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)
        ->call('openCreateModal')
        ->assertSee('New Plugin')
        ->assertSee('Slug (folder name)')
        ->set('newName', 'WhatsApp Alerts')
        // The folder name follows the name until the admin types their own.
        ->assertSet('newSlug', 'whatsapp-alerts')
        ->set('newDescription', 'Sends order updates over WhatsApp.')
        ->set('newAuthor', 'Codeware')
        ->set('newIcon', 'chat-bubble-left-right')
        ->call('createPlugin')
        ->assertHasNoErrors()
        ->assertSet('showCreateModal', false);

    $path = Plugins::path().'/whatsapp-alerts';

    expect(is_file($path.'/plugin.json'))->toBeTrue()
        ->and(is_file($path.'/index.blade.php'))->toBeTrue()
        ->and(is_file($path.'/routes.php'))->toBeTrue();

    $manifest = json_decode((string) file_get_contents($path.'/plugin.json'), true);

    expect($manifest)->toMatchArray([
        'name' => 'WhatsApp Alerts',
        'version' => '1.0.0',
        'description' => 'Sends order updates over WhatsApp.',
        'author' => 'Codeware',
        'icon' => 'chat-bubble-left-right',
        'default' => false,
    ]);

    // It is listed straight away, but off until the admin is happy with the files.
    expect(Plugins::find('whatsapp-alerts')['active'])->toBeFalse();
});

it('opens the generated screen once the new plugin is activated', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)
        ->set('newName', 'WhatsApp Alerts')
        ->set('newDescription', 'Sends order updates over WhatsApp.')
        ->call('createPlugin')
        ->assertHasNoErrors()
        ->call('toggle', 'whatsapp-alerts');

    $this->get(route('admin.plugins.show', 'whatsapp-alerts'))
        ->assertOk()
        ->assertSee('WhatsApp Alerts')
        ->assertSee('Sends order updates over WhatsApp.');
});

it('refuses to create a plugin with a slug that is taken or unusable', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)
        ->set('newName', 'Clock')
        ->set('newSlug', 'clock')
        ->call('createPlugin')
        ->assertHasErrors(['newSlug' => 'A plugin with this slug already exists.']);

    Livewire::test(Index::class)
        ->set('newName', 'Clock Two')
        ->set('newSlug', 'Clock Two!')
        ->call('createPlugin')
        ->assertHasErrors(['newSlug' => 'Use lowercase letters, numbers, dashes and underscores only.']);

    expect(is_dir(Plugins::path().'/clock'))->toBeTrue()
        ->and(is_dir(Plugins::path().'/Clock Two!'))->toBeFalse();
});

it('downloads an installed plugin as a zip holding just its folder', function () {
    $this->actingAs($this->admin);

    $component = Livewire::test(Index::class)
        ->call('downloadPlugin', 'clock')
        ->assertFileDownloaded('clock.zip');

    $download = $component->effects['download'];
    $zipPath = tempnam(sys_get_temp_dir(), 'plugin-export').'.zip';
    file_put_contents($zipPath, base64_decode($download['content']));

    $zip = new ZipArchive;
    $zip->open($zipPath);
    $names = collect(range(0, $zip->numFiles - 1))->map(fn (int $i) => $zip->getNameIndex($i));
    $zip->close();
    @unlink($zipPath);

    // One folder, named after the plugin — the shape the install screen wants.
    expect($download['name'])->toBe('clock.zip')
        ->and($names)->toContain('clock/plugin.json', 'clock/index.blade.php', 'clock/header.blade.php')
        ->and($names->filter(fn (string $n) => str_starts_with($n, 'calendar/'))->all())->toBe([]);
});

it('hands back a zip that installs the same plugin again after it is deleted', function () {
    $this->actingAs($this->admin);

    Livewire::test(Index::class)
        ->set('newName', 'Round Trip')
        ->set('newSlug', 'round-trip')
        ->call('createPlugin')
        ->assertHasNoErrors();

    $zipPath = Plugins::toZip('round-trip');

    expect(Plugins::delete('round-trip'))->toBeTrue()
        ->and(Plugins::find('round-trip'))->toBeNull();

    $slug = Plugins::installFromZip($zipPath);
    @unlink($zipPath);

    expect($slug)->toBe('round-trip')
        ->and(Plugins::find('round-trip')['name'])->toBe('Round Trip')
        // Installing does not switch it on — that stays the admin's decision.
        ->and(Plugins::find('round-trip')['active'])->toBeFalse();
});
