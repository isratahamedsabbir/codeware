<?php

use App\Livewire\Admin\ShippingMethods\Index as ShippingMethodsIndex;
use App\Models\ShippingMethod;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

function createShippingMethod(array $attributes = []): ShippingMethod
{
    return ShippingMethod::create(array_merge([
        'name' => fake()->unique()->words(2, true),
        'cost' => fake()->randomFloat(2, 5, 50),
        'status' => 'active',
    ], $attributes));
}

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $method = createShippingMethod();

    Livewire::test(ShippingMethodsIndex::class)
        ->call('toggleSelect', $method->id)
        ->assertSee('Shipping Methods')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $methods = [createShippingMethod(), createShippingMethod()];

    $component = Livewire::test(ShippingMethodsIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $methods[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the delete and export buttons only once a selection exists', function () {
    $method = createShippingMethod();

    Livewire::test(ShippingMethodsIndex::class)
        ->assertDontSee('Export (')
        ->assertDontSee('Delete (')
        ->call('toggleSelect', $method->id)
        ->assertSee('Export (1)')
        ->assertSee('Delete (1)');
});

it('toggles a shipping method id in and out of the selection', function () {
    $method = createShippingMethod();

    Livewire::test(ShippingMethodsIndex::class)
        ->call('toggleSelect', $method->id)
        ->assertSet('selectedIds', [$method->id])
        ->call('toggleSelect', $method->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(ShippingMethodsIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete modal once a selection exists', function () {
    $method = createShippingMethod();

    Livewire::test(ShippingMethodsIndex::class)
        ->call('toggleSelect', $method->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'shipping-method-bulk-delete');
});

it('bulk deletes the selected shipping methods and clears the selection', function () {
    $toDelete = [createShippingMethod(), createShippingMethod()];
    $keep = createShippingMethod();

    Livewire::test(ShippingMethodsIndex::class)
        ->set('selectedIds', array_map(fn ($m) => $m->id, $toDelete))
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify')
        ->assertDispatched('close-modal', name: 'shipping-method-bulk-delete');

    expect(ShippingMethod::whereIn('id', array_map(fn ($m) => $m->id, $toDelete))->count())->toBe(0);
    expect(ShippingMethod::find($keep->id))->not->toBeNull();
});

it('disables the row actions dropdown trigger while a bulk selection is active', function () {
    $method = createShippingMethod();

    Livewire::test(ShippingMethodsIndex::class)
        ->call('toggleSelect', $method->id)
        ->assertSeeHtml('disabled');
});
