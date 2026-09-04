<?php

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Models\Language;
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

it('keeps a fixed section order with General and Images first, regardless of row insertion order', function () {
    // Inserted out of order to prove the view doesn't just rely on DB row order,
    // which isn't guaranteed without an ORDER BY. General and Images render as
    // separate side-by-side cards in the General tab (see the view) — both need
    // to be present and correctly populated. 'frontend' (site_theme) is excluded
    // from this listing entirely — it renders under the Theme tab instead.
    Setting::factory()->create(['key' => 'site_theme', 'value' => 'default', 'group' => 'frontend', 'type' => 'select']);
    Setting::factory()->create(['key' => 'app_locale', 'value' => 'en', 'group' => 'localization', 'type' => 'string']);
    Setting::factory()->create(['key' => 'site_icon', 'value' => '', 'group' => 'images', 'type' => 'string']);
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Codeware', 'group' => 'general', 'type' => 'string']);

    Livewire::test(SettingsIndex::class)
        ->assertViewHas('groupedSettings', function ($groups) {
            return $groups->keys()->all() === ['general', 'images', 'localization']
                && $groups->get('general')->pluck('key')->all() === ['site_name']
                && $groups->get('images')->pluck('key')->all() === ['site_icon'];
        });
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
    expect(Setting::where('key', 'loader')->exists())->toBeTrue();
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

it('saves the loader gif through the form', function () {
    Livewire::test(SettingsIndex::class)
        ->set('settings.loader', '/storage/loader.gif')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'loader')->value('value'))->toBe('/storage/loader.gif');
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

it('does not show the floating button (or any other "other"-group) setting in the general tab', function () {
    Setting::factory()->create(['key' => 'floating_button_enabled', 'value' => '0', 'group' => 'other', 'type' => 'boolean']);

    Livewire::test(SettingsIndex::class)
        ->assertViewHas('groupedSettings', function ($groups) {
            return ! $groups->has('other');
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

it('renders app locale as a select populated from active languages, not a free-text input', function () {
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);
    Setting::factory()->create(['key' => 'app_locale', 'value' => 'en', 'group' => 'localization', 'type' => 'string']);

    Livewire::test(SettingsIndex::class)
        ->assertSee('App Locale')
        ->assertSeeHtml('<option value="en">English</option>')
        ->assertSeeHtml('<option value="bn">বাংলা</option>');
});

it('renders site theme as a select populated from available theme folders, not a free-text input', function () {
    Setting::factory()->create(['key' => 'site_theme', 'value' => 'default', 'group' => 'frontend', 'type' => 'select']);

    Livewire::test(SettingsIndex::class)
        ->assertSee('Site Design')
        ->assertSeeHtml('<option value="default">Default</option>')
        ->assertSeeHtml('<option value="ecommerce">Ecommerce</option>')
        ->assertSeeHtml('<option value="portfolio">Portfolio</option>');
});

it('does not double-render the label for image settings', function () {
    Setting::factory()->create(['key' => 'site_icon_white', 'value' => '', 'group' => 'images', 'type' => 'string']);

    $html = Livewire::test(SettingsIndex::class)->html();

    expect(substr_count($html, 'White Icon'))->toBe(1)
        ->and($html)->not->toContain('Site Icon White');
});

it('renders timezone as a select grouped by region, not a free-text input', function () {
    Setting::factory()->create(['key' => 'timezone', 'value' => 'UTC', 'group' => 'localization', 'type' => 'select']);

    Livewire::test(SettingsIndex::class)
        ->assertSee('Timezone')
        ->assertSeeHtml('<option value="Asia/Dhaka">Asia/Dhaka</option>');
});

it('defaults a new constant field to the textarea type', function () {
    Livewire::test(SettingsIndex::class)
        ->call('addConstant')
        ->assertSet('constants.0.type', 'textarea');
});

it('switches a constant value field to file picker when File is clicked', function () {
    Livewire::test(SettingsIndex::class)
        ->call('addConstant')
        ->assertDontSee('mp-constants-0-value', false)
        ->call('setConstantType', 0, 'file')
        ->assertSet('constants.0.type', 'file')
        ->assertSee('mp-constants-0-value', false);
});

it('saves constants and makes them readable through the setting_constant() helper', function () {
    Livewire::test(SettingsIndex::class)
        ->call('addConstant')
        ->set('constants.0.key', 'support_email')
        ->set('constants.0.value', 'support@example.com')
        ->call('save');

    expect(setting_constant('support_email'))->toBe('support@example.com');
});

it('drops blank constant rows when saving, but keeps filled ones', function () {
    Livewire::test(SettingsIndex::class)
        ->call('addConstant')
        ->call('addConstant')
        ->set('constants.0.key', 'kept')
        ->set('constants.0.value', '1')
        ->set('constants.1.key', '')
        ->set('constants.1.value', '')
        ->call('save');

    $constants = json_decode(Setting::get('constants'), true);

    expect($constants)->toHaveCount(1)
        ->and($constants[0]['key'])->toBe('kept');
});

it('rejects duplicate constant keys', function () {
    Livewire::test(SettingsIndex::class)
        ->call('addConstant')
        ->call('addConstant')
        ->set('constants.0.key', 'dup')
        ->set('constants.0.value', 'a')
        ->set('constants.1.key', 'dup')
        ->set('constants.1.value', 'b')
        ->call('save')
        ->assertHasErrors(['constants']);
});
