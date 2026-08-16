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
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Codeware', 'group' => 'general', 'type' => 'string']);

    $component = Livewire::test(SettingsIndex::class);
    expect($component->get('settings.site_name'))->toBe('Codeware');
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
    expect(Setting::where('key', 'site_icon')->exists())->toBeTrue();
    expect(Setting::where('key', 'favicon')->exists())->toBeTrue();
});

it('saves the site icon through the form', function () {
    Livewire::test(SettingsIndex::class)
        ->set('settings.site_icon', '/storage/site-icon.png')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'site_icon')->value('value'))->toBe('/storage/site-icon.png');
});

it('saves the favicon through the form', function () {
    Livewire::test(SettingsIndex::class)
        ->set('settings.favicon', '/storage/favicon.png')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'favicon')->value('value'))->toBe('/storage/favicon.png');
});

it('renders the favicon in the admin layout head', function () {
    Setting::set('favicon', '/storage/favicon.png');

    $this->actingAs($this->admin)
        ->get('/admin/posts')
        ->assertOk()
        ->assertSee('rel="icon" href="/storage/favicon.png"', false);
});

it('renders the site icon in the admin layout favicon and sidebar logo', function () {
    Setting::set('site_icon', '/storage/site-icon.png');

    $this->actingAs($this->admin)
        ->get('/admin/posts')
        ->assertOk()
        ->assertSee('href="/storage/site-icon.png"', false)
        ->assertSee('src="/storage/site-icon.png"', false);
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

it('does not show colors settings in the general tab', function () {
    Setting::factory()->create(['key' => 'primary_color', 'value' => '#2563eb', 'group' => 'colors', 'type' => 'color']);

    Livewire::test(SettingsIndex::class)
        ->assertViewHas('groupedSettings', function ($groups) {
            return ! $groups->has('colors');
        });
});

it('renders the other tab with color fields', function () {
    Setting::factory()->create(['key' => 'primary_color', 'value' => '#2563eb', 'group' => 'colors', 'type' => 'color']);
    Setting::factory()->create(['key' => 'secondary_color', 'value' => '#059669', 'group' => 'colors', 'type' => 'color']);

    Livewire::test(SettingsIndex::class)
        ->assertSee('Other')
        ->assertViewHas('colorSettings', function ($settings) {
            return $settings->pluck('key')->contains('primary_color')
                && $settings->pluck('key')->contains('secondary_color');
        });
});

it('saves colors settings through the form', function () {
    Livewire::test(SettingsIndex::class)
        ->set('settings.primary_color', '#7cc242')
        ->set('settings.secondary_color', '#2563eb')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'primary_color')->value('value'))->toBe('#7cc242');
    expect(Setting::where('key', 'secondary_color')->value('value'))->toBe('#2563eb');
});

it('saves seo settings through the form', function () {
    Setting::factory()->create(['key' => 'seo_meta_title', 'value' => '', 'group' => 'seo', 'type' => 'string']);

    Livewire::test(SettingsIndex::class)
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

it('seeder creates seo settings', function () {
    $this->artisan('db:seed', ['--class' => 'SettingsSeeder']);

    foreach (['seo_meta_title', 'seo_meta_description', 'seo_og_title', 'seo_og_description', 'seo_og_image'] as $key) {
        expect(Setting::where('key', $key)->exists())->toBeTrue();
    }
});

it('does not show theme settings in the general tab', function () {
    Setting::factory()->create(['key' => 'theme_mode', 'value' => 'dark', 'group' => 'theme', 'type' => 'string']);

    Livewire::test(SettingsIndex::class)
        ->assertViewHas('groupedSettings', function ($groups) {
            return ! $groups->has('theme');
        });
});

it('renders the theme tab with mode and accent fields', function () {
    Livewire::test(SettingsIndex::class)
        ->assertSee('Theme')
        ->assertSee('Theme Mode')
        ->assertSee('Accent Color')
        ->assertSee('Custom hex');
});

it('saves theme settings through the form', function () {
    Livewire::test(SettingsIndex::class)
        ->set('settings.theme_mode', 'dark')
        ->set('settings.theme_accent', '#7cc242')
        ->set('settings.theme_name', 'Forest Green')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('admin-theme-changed');

    expect(Setting::where('key', 'theme_mode')->value('value'))->toBe('dark');
    expect(Setting::where('key', 'theme_accent')->value('value'))->toBe('#7cc242');
    expect(Setting::where('key', 'theme_name')->value('value'))->toBe('Forest Green');
});

it('seeder creates theme settings', function () {
    $this->artisan('db:seed', ['--class' => 'SettingsSeeder']);

    foreach (['theme_mode', 'theme_name', 'theme_accent'] as $key) {
        expect(Setting::where('key', $key)->exists())->toBeTrue();
    }
});

it('seeder creates social link settings', function () {
    $this->artisan('db:seed', ['--class' => 'SettingsSeeder']);

    foreach (['facebook_url', 'twitter_url', 'instagram_url', 'youtube_url', 'linkedin_url', 'tiktok_url', 'whatsapp_number'] as $key) {
        expect(Setting::where('key', $key)->exists())->toBeTrue();
    }
});

it('renders the social links section in the other tab', function () {
    Livewire::test(SettingsIndex::class)
        ->assertSee('Social Links')
        ->assertSee('Facebook')
        ->assertSee('Instagram')
        ->assertSee('YouTube')
        ->assertSee('LinkedIn')
        ->assertSee('WhatsApp');
});

it('saves social link settings through the form', function () {
    Livewire::test(SettingsIndex::class)
        ->set('settings.facebook_url', 'https://facebook.com/codeware')
        ->set('settings.instagram_url', 'https://instagram.com/codeware')
        ->set('settings.whatsapp_number', '+8801700000000')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'facebook_url')->value('value'))->toBe('https://facebook.com/codeware');
    expect(Setting::where('key', 'instagram_url')->value('value'))->toBe('https://instagram.com/codeware');
    expect(Setting::where('key', 'whatsapp_number')->value('value'))->toBe('+8801700000000');
});

it('seeder creates currency settings', function () {
    $this->artisan('db:seed', ['--class' => 'SettingsSeeder']);

    foreach (['currency_code', 'currency_symbol', 'currency_position', 'decimal_places'] as $key) {
        expect(Setting::where('key', $key)->exists())->toBeTrue();
    }
});

it('does not show currency settings in the general tab', function () {
    Setting::factory()->create(['key' => 'currency_code', 'value' => 'BDT', 'group' => 'currency', 'type' => 'string']);

    Livewire::test(SettingsIndex::class)
        ->assertViewHas('groupedSettings', function ($groups) {
            return ! $groups->has('currency');
        });
});

it('renders the currency tab with currency fields', function () {
    Livewire::test(SettingsIndex::class)
        ->assertSee('Currency')
        ->assertSee('Currency Code')
        ->assertSee('Symbol Position')
        ->assertSee('Decimal Places')
        ->assertSee('Preview');
});

it('saves currency settings through the form', function () {
    Livewire::test(SettingsIndex::class)
        ->set('settings.currency_code', 'USD')
        ->set('settings.currency_symbol', '$')
        ->set('settings.currency_position', 'right')
        ->set('settings.decimal_places', '2')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'currency_code')->value('value'))->toBe('USD');
    expect(Setting::where('key', 'currency_symbol')->value('value'))->toBe('$');
    expect(Setting::where('key', 'currency_position')->value('value'))->toBe('right');
    expect(Setting::where('key', 'decimal_places')->value('value'))->toBe('2');
});
