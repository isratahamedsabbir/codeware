<?php

use App\Livewire\Admin\Seo\Index as SeoIndex;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('renders the seo screen with meta and open graph fields', function () {
    Livewire::test(SeoIndex::class)
        ->assertStatus(200)
        ->assertSee('Meta Title')
        ->assertSee('Meta Description')
        ->assertSee('Open Graph')
        ->assertSee('OG Image');
});

it('is reachable at its own admin route', function () {
    $this->get(route('admin.seo'))->assertOk();
});

it('saves seo settings through the form', function () {
    Setting::factory()->create(['key' => 'seo_meta_title', 'value' => '', 'group' => 'seo', 'type' => 'string']);

    Livewire::test(SeoIndex::class)
        ->set('settings.seo_meta_title', 'Codeware – Fresh Agriculture')
        ->set('settings.seo_meta_description', 'Buy fresh produce online.')
        ->set('settings.seo_og_title', 'Codeware')
        ->set('settings.seo_og_description', 'Fresh produce, delivered.')
        ->set('settings.seo_og_image', '/storage/og.png')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'seo_meta_title')->value('value'))->toBe('Codeware – Fresh Agriculture');
    expect(Setting::where('key', 'seo_meta_description')->value('value'))->toBe('Buy fresh produce online.');
    expect(Setting::where('key', 'seo_og_title')->value('value'))->toBe('Codeware');
    expect(Setting::where('key', 'seo_og_description')->value('value'))->toBe('Fresh produce, delivered.');
    expect(Setting::where('key', 'seo_og_image')->value('value'))->toBe('/storage/og.png');
});

it('adds and removes canonical base links', function () {
    Livewire::test(SeoIndex::class)
        ->call('addCanonicalUrl')
        ->set('canonicalUrls.1', 'https://example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(json_decode(Setting::get('seo_canonical_urls'), true))->toBe(['https://example.com']);
});

it('blocks staff from the seo screen', function () {
    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create(['is_admin' => false]);
    $staff->assignRole('staff');

    $this->actingAs($staff)->get(route('admin.seo'))->assertForbidden();
});
