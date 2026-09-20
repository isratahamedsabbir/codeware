<?php

use App\Livewire\Admin\ShopToggle;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

it('hides the shop toggle from the admin header by default', function () {
    $this->actingAs($this->admin)
        ->get(config('app.admin_url'))
        ->assertOk()
        ->assertDontSee('Shop On')
        ->assertDontSee('Shop Off');
});

it('shows the shop toggle in the admin header once enabled in settings', function () {
    Setting::set('shop_toggle_enabled', '1');

    $this->actingAs($this->admin)
        ->get(config('app.admin_url'))
        ->assertOk()
        ->assertSee('Shop On');
});

it('defaults shop_enabled to on', function () {
    expect((bool) Setting::get('shop_enabled', true))->toBeTrue();
});

it('toggles the shop status and persists it', function () {
    $this->actingAs($this->admin);

    Livewire::test(ShopToggle::class)
        ->assertSet('enabled', true)
        ->call('toggle')
        ->assertSet('enabled', false);

    expect((bool) Setting::get('shop_enabled'))->toBeFalse();

    Livewire::test(ShopToggle::class)
        ->assertSet('enabled', false)
        ->call('toggle')
        ->assertSet('enabled', true);

    expect((bool) Setting::get('shop_enabled'))->toBeTrue();
});
