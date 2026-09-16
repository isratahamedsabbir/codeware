<?php

use App\Livewire\Admin\Divisions\Index as DivisionsIndex;
use App\Models\Country;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
    $this->country = Country::create(['name' => 'Bangladesh '.uniqid(), 'status' => 'active']);
});

function createDivision(int $countryId, array $overrides = []): Division
{
    return Division::create(array_merge([
        'country_id' => $countryId,
        'name' => 'Division '.uniqid(),
        'status' => 'active',
    ], $overrides));
}

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $division = createDivision($this->country->id);

    Livewire::test(DivisionsIndex::class)
        ->call('toggleSelect', $division->id)
        ->assertSee('Location')
        ->assertSee('Divisions')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $divisions = [createDivision($this->country->id), createDivision($this->country->id)];

    $component = Livewire::test(DivisionsIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $divisions[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $division = createDivision($this->country->id);

    Livewire::test(DivisionsIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $division->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $division->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles a division id in and out of the selection', function () {
    $division = createDivision($this->country->id);

    Livewire::test(DivisionsIndex::class)
        ->call('toggleSelect', $division->id)
        ->assertSet('selectedIds', [$division->id])
        ->call('toggleSelect', $division->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(DivisionsIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $division = createDivision($this->country->id);

    Livewire::test(DivisionsIndex::class)
        ->call('toggleSelect', $division->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'division-bulk-delete');
});

it('deletes every selected division and clears the selection', function () {
    $divisions = [createDivision($this->country->id), createDivision($this->country->id), createDivision($this->country->id)];
    $keep = createDivision($this->country->id);

    Livewire::test(DivisionsIndex::class)
        ->call('toggleSelect', $divisions[0]->id)
        ->call('toggleSelect', $divisions[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 divisions deleted successfully')
        ->assertDispatched('close-modal', name: 'division-bulk-delete');

    expect(Division::find($divisions[0]->id))->toBeNull()
        ->and(Division::find($divisions[1]->id))->toBeNull()
        ->and(Division::find($divisions[2]->id))->not->toBeNull()
        ->and(Division::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one division is deleted', function () {
    $division = createDivision($this->country->id);

    Livewire::test(DivisionsIndex::class)
        ->call('toggleSelect', $division->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 division deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $divisions = [createDivision($this->country->id), createDivision($this->country->id)];

    $component = Livewire::test(DivisionsIndex::class);

    $component->assertSeeHtml(route('admin.divisions.edit', $divisions[0]->id));

    $component->call('toggleSelect', $divisions[0]->id)
        ->assertDontSeeHtml(route('admin.divisions.edit', $divisions[0]->id))
        ->assertDontSeeHtml(route('admin.divisions.edit', $divisions[1]->id));
});

it('exports only the requested division ids as a downloadable csv', function () {
    $included = createDivision($this->country->id, ['name' => 'Included Division']);
    $excluded = createDivision($this->country->id, ['name' => 'Excluded Division']);

    $response = $this->get(route('admin.divisions.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included Division')
        ->and($csv)->not->toContain('Excluded Division');
});
