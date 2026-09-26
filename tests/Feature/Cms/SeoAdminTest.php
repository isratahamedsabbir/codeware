<?php

use App\Livewire\Admin\Seo\Index as SeoIndex;
use App\Models\Language;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('renders the seo screen with meta and open graph fields', function () {
    Livewire::test(SeoIndex::class)
        ->assertStatus(200)
        ->assertSee('Meta Title')
        ->assertSee('Meta Description')
        ->assertSee('OG Title')
        ->assertSee('OG Image');
});

it('is reachable at its own admin route', function () {
    $this->get(route('admin.seo'))->assertOk();
});

it('saves seo settings through the form', function () {
    Setting::factory()->create(['key' => 'seo_meta_title', 'value' => '', 'group' => 'seo', 'type' => 'string']);

    Livewire::test(SeoIndex::class)
        ->set('settings.seo_meta_title.en', 'Codeware – Fresh Agriculture')
        ->set('settings.seo_meta_description.en', 'Buy fresh produce online.')
        ->set('settings.seo_og_title.en', 'Codeware')
        ->set('settings.seo_og_description.en', 'Fresh produce, delivered.')
        ->set('settings.seo_og_image', '/storage/og.png')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::translations(Setting::where('key', 'seo_meta_title')->value('value')))
        ->toMatchArray(['en' => 'Codeware – Fresh Agriculture'])
        ->and(Setting::translations(Setting::where('key', 'seo_meta_description')->value('value')))
        ->toMatchArray(['en' => 'Buy fresh produce online.'])
        ->and(Setting::translations(Setting::where('key', 'seo_og_title')->value('value')))
        ->toMatchArray(['en' => 'Codeware'])
        ->and(Setting::translations(Setting::where('key', 'seo_og_description')->value('value')))
        ->toMatchArray(['en' => 'Fresh produce, delivered.'])
        ->and(Setting::where('key', 'seo_og_image')->value('value'))->toBe('/storage/og.png');
});

it('saves the global seo copy per language', function () {
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);
    Setting::factory()->create(['key' => 'seo_meta_title', 'value' => '', 'group' => 'seo', 'type' => 'string']);

    Livewire::test(SeoIndex::class)
        ->assertSee('Meta Title')
        ->set('settings.seo_meta_title.en', 'Fresh Agriculture')
        ->set('settings.seo_meta_title.bn', 'তাজা কৃষি পণ্য')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::translations(Setting::where('key', 'seo_meta_title')->value('value')))
        ->toMatchArray(['en' => 'Fresh Agriculture', 'bn' => 'তাজা কৃষি পণ্য']);
});

it('reads a pre-existing plain-string seo setting as the primary locale', function () {
    Setting::set('seo_meta_title', 'Legacy Global Title');

    expect(Setting::translated('seo_meta_title'))->toBe('Legacy Global Title');
});

it('serves the global seo copy in the requested language, falling back to the primary one', function () {
    Setting::set('seo_meta_title', json_encode(['en' => 'Fresh Agriculture', 'bn' => 'তাজা কৃষি পণ্য']));
    Setting::set('seo_meta_description', json_encode(['en' => 'Buy fresh produce online.', 'bn' => '']));

    expect(Setting::translated('seo_meta_title', 'bn'))->toBe('তাজা কৃষি পণ্য')
        ->and(Setting::translated('seo_meta_description', 'bn'))->toBe('Buy fresh produce online.');
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
    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)->get(route('admin.seo'))->assertForbidden();
});
