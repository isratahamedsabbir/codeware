<?php

use App\Livewire\Admin\Plugins\Index;
use App\Livewire\Admin\Plugins\Show;
use App\Models\MenuItem;
use App\Models\User;
use App\Support\Plugins;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    Plugins::flush();
});

afterEach(function () {
    File::deleteDirectory(Plugins::path().'/demo-plugin');
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
    $this->seed(Database\Seeders\AdminMenuSeeder::class);
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
