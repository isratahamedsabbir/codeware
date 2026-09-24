<?php

use App\Livewire\Admin\Env\Index as EnvIndex;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

// Belt-and-suspenders: this writes the same storage/framework/maintenance.php
// file the real dev server reads, so a failed assertion here must never leave
// the actual site down. `up` is a safe no-op when already up.
afterEach(function () {
    Artisan::call('up');
});

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('starts with maintenance mode reported as off', function () {
    expect(app()->isDownForMaintenance())->toBeFalse();

    Livewire::test(EnvIndex::class)
        ->assertSet('maintenanceMode', false);
});

it('opens a confirmation modal before enabling maintenance mode', function () {
    Livewire::test(EnvIndex::class)
        ->call('confirmEnableMaintenanceMode')
        ->assertDispatched('open-modal', name: 'maintenance-mode-confirm');

    expect(app()->isDownForMaintenance())->toBeFalse();
});

it('puts the app into maintenance mode and reflects that back on the component', function () {
    Livewire::test(EnvIndex::class)
        ->call('enableMaintenanceMode')
        ->assertSet('maintenanceMode', true)
        ->assertDispatched('close-modal', name: 'maintenance-mode-confirm');

    expect(app()->isDownForMaintenance())->toBeTrue();
});

it('blocks the public site but keeps the admin panel and login reachable while enabled', function () {
    Livewire::test(EnvIndex::class)->call('enableMaintenanceMode');

    $this->get('/')->assertStatus(503);
    // Already authenticated in this test, so /login redirects away rather than
    // rendering — the point is just that it isn't blocked (503) like '/' is.
    $this->get('/login')->assertStatus(302);
    // The admin panel lives on its own host — a host-aware maintenance
    // middleware (App\Http\Middleware\PreventRequestsDuringMaintenance) lets
    // that host through so the panel that turns maintenance back off is never
    // cut off from doing so.
    $this->get(config('app.admin_url').'/env')->assertOk();
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

    Livewire::test(EnvIndex::class)
        ->assertSet('maintenanceMode', true)
        ->call('disableMaintenanceMode')
        ->assertSet('maintenanceMode', false);

    expect(app()->isDownForMaintenance())->toBeFalse();
    $this->get('/')->assertStatus(200);
});

it('can be turned on straight from the header switch, with no confirm modal', function () {
    Livewire::test(EnvIndex::class)
        ->assertSet('maintenanceMode', false)
        ->call('toggleMaintenanceMode')
        ->assertSet('maintenanceMode', true)
        ->assertDispatched('notify');

    expect(app()->isDownForMaintenance())->toBeTrue();
});

it('can be turned back off straight from the header switch', function () {
    Artisan::call('down');

    Livewire::test(EnvIndex::class)
        ->assertSet('maintenanceMode', true)
        ->call('toggleMaintenanceMode')
        ->assertSet('maintenanceMode', false)
        ->assertDispatched('notify');

    expect(app()->isDownForMaintenance())->toBeFalse();
});
