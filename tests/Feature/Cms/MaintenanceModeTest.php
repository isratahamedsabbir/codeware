<?php

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

// Belt-and-suspenders: this writes the same storage/framework/maintenance.php
// file the real dev server reads, so a failed assertion here must never leave
// the actual site down. `up` is a safe no-op when already up.
afterEach(function () {
    Artisan::call('up');
});

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('starts with maintenance mode reported as off', function () {
    expect(app()->isDownForMaintenance())->toBeFalse();

    Livewire::test(SettingsIndex::class)
        ->assertSet('maintenanceMode', false);
});

it('opens a confirmation modal before enabling maintenance mode', function () {
    Livewire::test(SettingsIndex::class)
        ->call('confirmEnableMaintenanceMode')
        ->assertDispatched('open-modal', name: 'maintenance-mode-confirm');

    expect(app()->isDownForMaintenance())->toBeFalse();
});

it('puts the app into maintenance mode and reflects that back on the component', function () {
    Livewire::test(SettingsIndex::class)
        ->call('enableMaintenanceMode')
        ->assertSet('maintenanceMode', true)
        ->assertDispatched('close-modal', name: 'maintenance-mode-confirm');

    expect(app()->isDownForMaintenance())->toBeTrue();
});

it('blocks the public site but keeps the admin panel and login reachable while enabled', function () {
    Livewire::test(SettingsIndex::class)->call('enableMaintenanceMode');

    $this->get('/')->assertStatus(503);
    // Already authenticated in this test, so /login redirects away rather than
    // rendering — the point is just that it isn't blocked (503) like '/' is.
    $this->get('/login')->assertStatus(302);
    $this->get('/admin/settings')->assertOk();
});

it('brings the site back online', function () {
    Artisan::call('down');
    expect(app()->isDownForMaintenance())->toBeTrue();

    Livewire::test(SettingsIndex::class)
        ->assertSet('maintenanceMode', true)
        ->call('disableMaintenanceMode')
        ->assertSet('maintenanceMode', false);

    expect(app()->isDownForMaintenance())->toBeFalse();
    $this->get('/')->assertStatus(200);
});
