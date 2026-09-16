<?php

use App\Livewire\Admin\ProductAttributes\Index as ProductAttributesIndex;
use App\Models\ProductAttribute;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

function createProductAttribute(array $overrides = []): ProductAttribute
{
    return ProductAttribute::create(array_merge([
        'name' => 'Color '.uniqid(),
        'values' => ['Red', 'Blue'],
    ], $overrides));
}

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $attribute = createProductAttribute();

    Livewire::test(ProductAttributesIndex::class)
        ->call('toggleSelect', $attribute->id)
        ->assertSee('Products')
        ->assertSee('Product Attributes')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $attributes = [createProductAttribute(), createProductAttribute()];

    $component = Livewire::test(ProductAttributesIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $attributes[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $attribute = createProductAttribute();

    Livewire::test(ProductAttributesIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $attribute->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $attribute->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles an attribute id in and out of the selection', function () {
    $attribute = createProductAttribute();

    Livewire::test(ProductAttributesIndex::class)
        ->call('toggleSelect', $attribute->id)
        ->assertSet('selectedIds', [$attribute->id])
        ->call('toggleSelect', $attribute->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(ProductAttributesIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $attribute = createProductAttribute();

    Livewire::test(ProductAttributesIndex::class)
        ->call('toggleSelect', $attribute->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'product-attribute-bulk-delete');
});

it('deletes every selected attribute and clears the selection', function () {
    $attributes = [createProductAttribute(), createProductAttribute(), createProductAttribute()];
    $keep = createProductAttribute();

    Livewire::test(ProductAttributesIndex::class)
        ->call('toggleSelect', $attributes[0]->id)
        ->call('toggleSelect', $attributes[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 attributes deleted successfully')
        ->assertDispatched('close-modal', name: 'product-attribute-bulk-delete');

    expect(ProductAttribute::find($attributes[0]->id))->toBeNull()
        ->and(ProductAttribute::find($attributes[1]->id))->toBeNull()
        ->and(ProductAttribute::find($attributes[2]->id))->not->toBeNull()
        ->and(ProductAttribute::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one attribute is deleted', function () {
    $attribute = createProductAttribute();

    Livewire::test(ProductAttributesIndex::class)
        ->call('toggleSelect', $attribute->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 attribute deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $attributes = [createProductAttribute(), createProductAttribute()];

    $component = Livewire::test(ProductAttributesIndex::class);

    $component->assertSeeHtml(route('admin.product-attributes.edit', $attributes[0]->id));

    $component->call('toggleSelect', $attributes[0]->id)
        ->assertDontSeeHtml(route('admin.product-attributes.edit', $attributes[0]->id))
        ->assertDontSeeHtml(route('admin.product-attributes.edit', $attributes[1]->id));
});
