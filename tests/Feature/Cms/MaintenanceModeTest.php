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

it('does not block Livewire\'s own AJAX endpoint, so the toggle can turn itself back off', function () {
    Artisan::call('down');

    // Livewire::test() bypasses the HTTP kernel/middleware stack entirely, so it
    // can't catch this: the "disable maintenance mode" button round-trips through
    // Livewire's real update endpoint, which must itself be reachable while down.
    $response = $this->post(route('default-livewire.update'), []);

    expect($response->status())->not->toBe(503);
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
