<?php

use App\Models\Language;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    // The switcher only renders once more than one language is active.
    Language::create(['code' => 'en', 'name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);
});

it('shows the language switcher in the admin header by default', function () {
    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk()
        ->assertSee('Change language', false);
});

it('hides the language switcher from the admin header once disabled in settings', function () {
    Setting::set('language_switcher_enabled', '0');

    $this->actingAs($this->admin)
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('Change language', false);
});

it('defaults language_switcher_enabled to on', function () {
    expect((bool) Setting::get('language_switcher_enabled', true))->toBeTrue();
});
