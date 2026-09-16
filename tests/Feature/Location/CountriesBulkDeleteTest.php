<?php

use App\Livewire\Admin\Countries\Index as CountriesIndex;
use App\Models\Country;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

function createCountry(array $overrides = []): Country
{
    return Country::create(array_merge([
        'name' => 'Country '.uniqid(),
        'status' => 'active',
    ], $overrides));
}

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $country = createCountry();

    Livewire::test(CountriesIndex::class)
        ->call('toggleSelect', $country->id)
        ->assertSee('Location')
        ->assertSee('Countries')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $countries = [createCountry(), createCountry()];

    $component = Livewire::test(CountriesIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $countries[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $country = createCountry();

    Livewire::test(CountriesIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $country->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $country->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles a country id in and out of the selection', function () {
    $country = createCountry();

    Livewire::test(CountriesIndex::class)
        ->call('toggleSelect', $country->id)
        ->assertSet('selectedIds', [$country->id])
        ->call('toggleSelect', $country->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(CountriesIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $country = createCountry();

    Livewire::test(CountriesIndex::class)
        ->call('toggleSelect', $country->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'country-bulk-delete');
});

it('deletes every selected country and clears the selection', function () {
    $countries = [createCountry(), createCountry(), createCountry()];
    $keep = createCountry();

    Livewire::test(CountriesIndex::class)
        ->call('toggleSelect', $countries[0]->id)
        ->call('toggleSelect', $countries[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 countries deleted successfully')
        ->assertDispatched('close-modal', name: 'country-bulk-delete');

    expect(Country::find($countries[0]->id))->toBeNull()
        ->and(Country::find($countries[1]->id))->toBeNull()
        ->and(Country::find($countries[2]->id))->not->toBeNull()
        ->and(Country::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one country is deleted', function () {
    $country = createCountry();

    Livewire::test(CountriesIndex::class)
        ->call('toggleSelect', $country->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 country deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $countries = [createCountry(), createCountry()];

    $component = Livewire::test(CountriesIndex::class);

    $component->assertSeeHtml(route('admin.countries.edit', $countries[0]->id));

    $component->call('toggleSelect', $countries[0]->id)
        ->assertDontSeeHtml(route('admin.countries.edit', $countries[0]->id))
        ->assertDontSeeHtml(route('admin.countries.edit', $countries[1]->id));
});

it('exports only the requested country ids as a downloadable csv', function () {
    $included = createCountry(['name' => 'Included Country']);
    $excluded = createCountry(['name' => 'Excluded Country']);

    $response = $this->get(route('admin.countries.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included Country')
        ->and($csv)->not->toContain('Excluded Country');
});
