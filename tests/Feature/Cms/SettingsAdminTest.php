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

it('renders the SEO tab with meta and open graph fields', function () {
    Livewire::test(SettingsIndex::class)
        ->assertSee('SEO')
        ->assertSee('Meta Title')
        ->assertSee('Meta Description')
        ->assertSee('Open Graph')
        ->assertSee('OG Image');
});

it('does not show SEO settings in the general tab', function () {
    Setting::factory()->create(['key' => 'seo_meta_title', 'value' => 'Hidden', 'group' => 'seo', 'type' => 'string']);

    Livewire::test(SettingsIndex::class)
        ->assertViewHas('groupedSettings', function ($groups) {
            return ! $groups->has('seo');
        });
});

it('saves seo settings through the form', function () {
    Setting::factory()->create(['key' => 'seo_meta_title', 'value' => '', 'group' => 'seo', 'type' => 'string']);

    Livewire::test(SettingsIndex::class)
        ->set('settings.seo_meta_title', 'Agrosal – Fresh Agriculture')
        ->set('settings.seo_meta_description', 'Buy fresh produce online.')
        ->set('settings.seo_og_title', 'Agrosal')
        ->set('settings.seo_og_description', 'Fresh produce, delivered.')
        ->set('settings.seo_og_image', '/storage/og.png')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'seo_meta_title')->value('value'))->toBe('Agrosal – Fresh Agriculture');
    expect(Setting::where('key', 'seo_meta_description')->value('value'))->toBe('Buy fresh produce online.');
    expect(Setting::where('key', 'seo_og_title')->value('value'))->toBe('Agrosal');
    expect(Setting::where('key', 'seo_og_description')->value('value'))->toBe('Fresh produce, delivered.');
    expect(Setting::where('key', 'seo_og_image')->value('value'))->toBe('/storage/og.png');
});

it('seeder creates seo settings', function () {
    $this->artisan('db:seed', ['--class' => 'SettingsSeeder']);

    foreach (['seo_meta_title', 'seo_meta_description', 'seo_og_title', 'seo_og_description', 'seo_og_image'] as $key) {
        expect(Setting::where('key', $key)->exists())->toBeTrue();
    }
});
