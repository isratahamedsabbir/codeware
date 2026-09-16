<?php

use App\Livewire\Admin\Upazilas\Index as UpazilasIndex;
use App\Models\Country;
use App\Models\District;
use App\Models\Division;
use App\Models\Upazila;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
    $this->country = Country::create(['name' => 'Bangladesh '.uniqid(), 'status' => 'active']);
    $this->division = Division::create(['country_id' => $this->country->id, 'name' => 'Division '.uniqid(), 'status' => 'active']);
    $this->district = District::create(['division_id' => $this->division->id, 'name' => 'District '.uniqid(), 'status' => 'active']);
});

function createUpazila(int $districtId, array $overrides = []): Upazila
{
    return Upazila::create(array_merge([
        'district_id' => $districtId,
        'name' => 'Upazila '.uniqid(),
        'status' => 'active',
    ], $overrides));
}

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $upazila = createUpazila($this->district->id);

    Livewire::test(UpazilasIndex::class)
        ->call('toggleSelect', $upazila->id)
        ->assertSee('Location')
        ->assertSee('Upazilas')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $upazilas = [createUpazila($this->district->id), createUpazila($this->district->id)];

    $component = Livewire::test(UpazilasIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $upazilas[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $upazila = createUpazila($this->district->id);

    Livewire::test(UpazilasIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $upazila->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $upazila->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles an upazila id in and out of the selection', function () {
    $upazila = createUpazila($this->district->id);

    Livewire::test(UpazilasIndex::class)
        ->call('toggleSelect', $upazila->id)
        ->assertSet('selectedIds', [$upazila->id])
        ->call('toggleSelect', $upazila->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(UpazilasIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $upazila = createUpazila($this->district->id);

    Livewire::test(UpazilasIndex::class)
        ->call('toggleSelect', $upazila->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'upazila-bulk-delete');
});

it('deletes every selected upazila and clears the selection', function () {
    $upazilas = [createUpazila($this->district->id), createUpazila($this->district->id), createUpazila($this->district->id)];
    $keep = createUpazila($this->district->id);

    Livewire::test(UpazilasIndex::class)
        ->call('toggleSelect', $upazilas[0]->id)
        ->call('toggleSelect', $upazilas[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 upazilas deleted successfully')
        ->assertDispatched('close-modal', name: 'upazila-bulk-delete');

    expect(Upazila::find($upazilas[0]->id))->toBeNull()
        ->and(Upazila::find($upazilas[1]->id))->toBeNull()
        ->and(Upazila::find($upazilas[2]->id))->not->toBeNull()
        ->and(Upazila::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one upazila is deleted', function () {
    $upazila = createUpazila($this->district->id);

    Livewire::test(UpazilasIndex::class)
        ->call('toggleSelect', $upazila->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 upazila deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $upazilas = [createUpazila($this->district->id), createUpazila($this->district->id)];

    $component = Livewire::test(UpazilasIndex::class);

    $component->assertSeeHtml(route('admin.upazilas.edit', $upazilas[0]->id));

    $component->call('toggleSelect', $upazilas[0]->id)
        ->assertDontSeeHtml(route('admin.upazilas.edit', $upazilas[0]->id))
        ->assertDontSeeHtml(route('admin.upazilas.edit', $upazilas[1]->id));
});

it('exports only the requested upazila ids as a downloadable csv', function () {
    $included = createUpazila($this->district->id, ['name' => 'Included Upazila']);
    $excluded = createUpazila($this->district->id, ['name' => 'Excluded Upazila']);

    $response = $this->get(route('admin.upazilas.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included Upazila')
        ->and($csv)->not->toContain('Excluded Upazila');
});
