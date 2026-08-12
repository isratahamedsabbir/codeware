<?php

use App\Livewire\Admin\ThemeSwitcher;
use App\Models\Setting;
use App\Models\User;
use App\Support\Theme;
use Livewire\Livewire;

it('renders the theme switcher', function () {
    Setting::set('theme_mode', 'light');

    Livewire::actingAs(User::factory()->create(['is_admin' => true]))
        ->test(ThemeSwitcher::class)
        ->assertStatus(200);
});

it('toggles theme mode from light to dark', function () {
    Setting::set('theme_mode', 'light');

    Livewire::actingAs(User::factory()->create(['is_admin' => true]))
        ->test(ThemeSwitcher::class)
        ->call('toggle');

    expect(Setting::get('theme_mode'))->toBe('dark');
    expect(Theme::isDark())->toBeTrue();
});

it('toggles theme mode from dark to light', function () {
    Setting::set('theme_mode', 'dark');

    Livewire::actingAs(User::factory()->create(['is_admin' => true]))
        ->test(ThemeSwitcher::class)
        ->call('toggle');

    expect(Setting::get('theme_mode'))->toBe('light');
});

it('dispatches the theme toggled event with the new mode', function () {
    Setting::set('theme_mode', 'light');

    Livewire::actingAs(User::factory()->create(['is_admin' => true]))
        ->test(ThemeSwitcher::class)
        ->call('toggle')
        ->assertDispatched('theme:toggled', mode: 'dark');
});

it('renders the theme switcher in the admin layout header', function () {
    Setting::set('theme_mode', 'light');

    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/admin/posts')
        ->assertOk()
        ->assertSee('admin.theme-switcher');
});
