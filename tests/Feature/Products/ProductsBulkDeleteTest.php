<?php

use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    // The breadcrumb is @included from inside this component's own template
    // (see the file header comment) rather than pushed into the layout, so
    // it must keep resolving to the real page — not Livewire's internal
    // update endpoint — on every subsequent request too.
    $product = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->call('toggleSelect', $product->id)
        ->assertSee('Products')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $products = Product::factory()->count(2)->create();

    $component = Livewire::test(ProductsIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $products[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $product = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $product->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $product->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles a product id in and out of the selection', function () {
    $product = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->call('toggleSelect', $product->id)
        ->assertSet('selectedIds', [$product->id])
        ->call('toggleSelect', $product->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(ProductsIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $product = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->call('toggleSelect', $product->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'product-bulk-delete');
});

it('soft-deletes every selected product, cascading its page, and clears the selection', function () {
    $products = Product::factory()->count(3)->create();
    $keep = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->call('toggleSelect', $products[0]->id)
        ->call('toggleSelect', $products[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 products deleted successfully')
        ->assertDispatched('close-modal', name: 'product-bulk-delete');

    expect(Product::find($products[0]->id))->toBeNull()
        ->and(Product::find($products[1]->id))->toBeNull()
        ->and(Product::find($products[2]->id))->not->toBeNull()
        ->and(Product::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one product is deleted', function () {
    $product = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->call('toggleSelect', $product->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 product deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $products = Product::factory()->count(2)->create();

    $component = Livewire::test(ProductsIndex::class);

    $component->assertSeeHtml(route('admin.products.edit', $products[0]->id));

    $component->call('toggleSelect', $products[0]->id)
        ->assertDontSeeHtml(route('admin.products.edit', $products[0]->id))
        ->assertDontSeeHtml(route('admin.products.edit', $products[1]->id));
});
