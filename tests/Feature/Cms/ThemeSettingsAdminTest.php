<?php

use App\Livewire\Admin\ThemeSettings\Index as ThemeSettings;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('renders the theme settings page', function () {
    Livewire::test(ThemeSettings::class)->assertStatus(200);
});

it('is reachable by admins via its own route', function () {
    $this->get(route('admin.theme-settings'))->assertOk();
});

it('loads existing theme settings into the form', function () {
    Setting::factory()->create(['key' => 'site_theme', 'value' => 'ecommerce', 'group' => 'frontend', 'type' => 'select']);
    Setting::factory()->create(['key' => 'site_tagline', 'value' => 'Shop smart', 'group' => 'frontend', 'type' => 'textarea']);
    Setting::factory()->create(['key' => 'chat_widget_enabled', 'value' => '0', 'group' => 'frontend', 'type' => 'boolean']);

    $component = Livewire::test(ThemeSettings::class);

    expect($component->get('settings.site_theme'))->toBe('ecommerce')
        ->and($component->get('settings.site_tagline'))->toBe('Shop smart')
        ->and($component->get('settings.chat_widget_enabled'))->toBe(false);
});

it('lists every installed theme folder as a selectable design', function () {
    Livewire::test(ThemeSettings::class)
        ->assertViewHas('themes', fn ($themes) => collect(['default', 'ecommerce', 'portfolio'])->diff(array_keys($themes))->isEmpty());
});

it('saves theme settings through Setting::set', function () {
    Setting::factory()->create(['key' => 'site_theme', 'value' => 'default', 'group' => 'frontend', 'type' => 'select']);
    Setting::factory()->create(['key' => 'home_hero_image', 'value' => '', 'group' => 'frontend', 'type' => 'string']);
    Setting::factory()->create(['key' => 'chat_widget_enabled', 'value' => '1', 'group' => 'frontend', 'type' => 'boolean']);

    Livewire::test(ThemeSettings::class)
        ->set('settings.site_theme', 'ecommerce')
        ->set('settings.home_hero_image', 'media/hero.jpg')
        ->set('settings.chat_widget_enabled', false)
        ->call('save');

    expect(Setting::where('key', 'site_theme')->value('value'))->toBe('ecommerce')
        ->and(Setting::where('key', 'home_hero_image')->value('value'))->toBe('media/hero.jpg')
        ->and(Setting::where('key', 'chat_widget_enabled')->value('value'))->toBe('0');
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
