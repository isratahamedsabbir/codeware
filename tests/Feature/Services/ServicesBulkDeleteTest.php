<?php

use App\Livewire\Admin\Services\Index as ServicesIndex;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $services = Service::factory()->count(2)->create();

    $component = Livewire::test(ServicesIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $services[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $service = Service::factory()->create();

    Livewire::test(ServicesIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $service->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $service->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles a service id in and out of the selection', function () {
    $service = Service::factory()->create();

    Livewire::test(ServicesIndex::class)
        ->call('toggleSelect', $service->id)
        ->assertSet('selectedIds', [$service->id])
        ->call('toggleSelect', $service->id)
        ->assertSet('selectedIds', []);
});

it('deletes every selected service and clears the selection', function () {
    $services = Service::factory()->count(3)->create();
    $keep = Service::factory()->create();

    Livewire::test(ServicesIndex::class)
        ->call('toggleSelect', $services[0]->id)
        ->call('toggleSelect', $services[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 services deleted successfully')
        ->assertDispatched('close-modal', name: 'service-bulk-delete');

    expect(Service::find($services[0]->id))->toBeNull()
        ->and(Service::find($services[1]->id))->toBeNull()
        ->and(Service::find($services[2]->id))->not->toBeNull()
        ->and(Service::find($keep->id))->not->toBeNull();
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $services = Service::factory()->count(2)->create();

    $component = Livewire::test(ServicesIndex::class);

    $component->assertSeeHtml(route('admin.services.edit', $services[0]->id));

    $component->call('toggleSelect', $services[0]->id)
        ->assertDontSeeHtml(route('admin.services.edit', $services[0]->id))
        ->assertDontSeeHtml(route('admin.services.edit', $services[1]->id));
});
