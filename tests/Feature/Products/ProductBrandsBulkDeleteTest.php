<?php

use App\Livewire\Admin\ProductBrands\Index as ProductBrandsIndex;
use App\Models\ProductBrand;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $brand = ProductBrand::factory()->create();

    Livewire::test(ProductBrandsIndex::class)
        ->call('toggleSelect', $brand->id)
        ->assertSee('Products')
        ->assertSee('Product Brands')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $brands = ProductBrand::factory()->count(2)->create();

    $component = Livewire::test(ProductBrandsIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $brands[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $brand = ProductBrand::factory()->create();

    Livewire::test(ProductBrandsIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $brand->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $brand->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles a brand id in and out of the selection', function () {
    $brand = ProductBrand::factory()->create();

    Livewire::test(ProductBrandsIndex::class)
        ->call('toggleSelect', $brand->id)
        ->assertSet('selectedIds', [$brand->id])
        ->call('toggleSelect', $brand->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(ProductBrandsIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $brand = ProductBrand::factory()->create();

    Livewire::test(ProductBrandsIndex::class)
        ->call('toggleSelect', $brand->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'product-brand-bulk-delete');
});

it('deletes every selected brand and clears the selection', function () {
    $brands = ProductBrand::factory()->count(3)->create();
    $keep = ProductBrand::factory()->create();

    Livewire::test(ProductBrandsIndex::class)
        ->call('toggleSelect', $brands[0]->id)
        ->call('toggleSelect', $brands[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 brands deleted successfully')
        ->assertDispatched('close-modal', name: 'product-brand-bulk-delete');

    expect(ProductBrand::find($brands[0]->id))->toBeNull()
        ->and(ProductBrand::find($brands[1]->id))->toBeNull()
        ->and(ProductBrand::find($brands[2]->id))->not->toBeNull()
        ->and(ProductBrand::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one brand is deleted', function () {
    $brand = ProductBrand::factory()->create();

    Livewire::test(ProductBrandsIndex::class)
        ->call('toggleSelect', $brand->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 brand deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $brands = ProductBrand::factory()->count(2)->create();

    $component = Livewire::test(ProductBrandsIndex::class);

    $component->assertSeeHtml(route('admin.product-brands.edit', $brands[0]->id));

    $component->call('toggleSelect', $brands[0]->id)
        ->assertDontSeeHtml(route('admin.product-brands.edit', $brands[0]->id))
        ->assertDontSeeHtml(route('admin.product-brands.edit', $brands[1]->id));
});
