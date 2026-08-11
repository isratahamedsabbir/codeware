<?php

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('renders settings index', function () {
    Livewire::test(SettingsIndex::class)->assertStatus(200);
});

it('loads existing settings into form', function () {
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Agrosal', 'group' => 'general', 'type' => 'string']);

    $component = Livewire::test(SettingsIndex::class);
    expect($component->get('settings.site_name'))->toBe('Agrosal');
});

it('saves settings', function () {
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Old Name', 'group' => 'general', 'type' => 'string']);

    Livewire::test(SettingsIndex::class)
        ->set('settings.site_name', 'New Name')
        ->call('save');

    expect(Setting::where('key', 'site_name')->value('value'))->toBe('New Name');
});

it('seeder creates required settings', function () {
    $this->artisan('db:seed', ['--class' => 'SettingsSeeder']);

    expect(Setting::where('key', 'header_content')->exists())->toBeTrue();
    expect(Setting::where('key', 'footer_content')->exists())->toBeTrue();
    expect(Setting::where('key', 'site_name')->exists())->toBeTrue();
});
