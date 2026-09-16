<?php

use App\Livewire\Admin\Districts\Index as DistrictsIndex;
use App\Models\Country;
use App\Models\District;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
    $this->country = Country::create(['name' => 'Bangladesh '.uniqid(), 'status' => 'active']);
    $this->division = Division::create(['country_id' => $this->country->id, 'name' => 'Division '.uniqid(), 'status' => 'active']);
});

function createDistrict(int $divisionId, array $overrides = []): District
{
    return District::create(array_merge([
        'division_id' => $divisionId,
        'name' => 'District '.uniqid(),
        'status' => 'active',
    ], $overrides));
}

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $district = createDistrict($this->division->id);

    Livewire::test(DistrictsIndex::class)
        ->call('toggleSelect', $district->id)
        ->assertSee('Location')
        ->assertSee('Districts')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $districts = [createDistrict($this->division->id), createDistrict($this->division->id)];

    $component = Livewire::test(DistrictsIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $districts[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $district = createDistrict($this->division->id);

    Livewire::test(DistrictsIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $district->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $district->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles a district id in and out of the selection', function () {
    $district = createDistrict($this->division->id);

    Livewire::test(DistrictsIndex::class)
        ->call('toggleSelect', $district->id)
        ->assertSet('selectedIds', [$district->id])
        ->call('toggleSelect', $district->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(DistrictsIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $district = createDistrict($this->division->id);

    Livewire::test(DistrictsIndex::class)
        ->call('toggleSelect', $district->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'district-bulk-delete');
});

it('deletes every selected district and clears the selection', function () {
    $districts = [createDistrict($this->division->id), createDistrict($this->division->id), createDistrict($this->division->id)];
    $keep = createDistrict($this->division->id);

    Livewire::test(DistrictsIndex::class)
        ->call('toggleSelect', $districts[0]->id)
        ->call('toggleSelect', $districts[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 districts deleted successfully')
        ->assertDispatched('close-modal', name: 'district-bulk-delete');

    expect(District::find($districts[0]->id))->toBeNull()
        ->and(District::find($districts[1]->id))->toBeNull()
        ->and(District::find($districts[2]->id))->not->toBeNull()
        ->and(District::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one district is deleted', function () {
    $district = createDistrict($this->division->id);

    Livewire::test(DistrictsIndex::class)
        ->call('toggleSelect', $district->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 district deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $districts = [createDistrict($this->division->id), createDistrict($this->division->id)];

    $component = Livewire::test(DistrictsIndex::class);

    $component->assertSeeHtml(route('admin.districts.edit', $districts[0]->id));

    $component->call('toggleSelect', $districts[0]->id)
        ->assertDontSeeHtml(route('admin.districts.edit', $districts[0]->id))
        ->assertDontSeeHtml(route('admin.districts.edit', $districts[1]->id));
});

it('exports only the requested district ids as a downloadable csv', function () {
    $included = createDistrict($this->division->id, ['name' => 'Included District']);
    $excluded = createDistrict($this->division->id, ['name' => 'Excluded District']);

    $response = $this->get(route('admin.districts.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included District')
        ->and($csv)->not->toContain('Excluded District');
});
