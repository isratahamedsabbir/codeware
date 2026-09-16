<?php

use App\Livewire\Admin\ProductCategories\Index as ProductCategoriesIndex;
use App\Models\ProductCategory;
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
    $category = ProductCategory::factory()->create();

    Livewire::test(ProductCategoriesIndex::class)
        ->call('toggleSelect', $category->id)
        ->assertSee('Products')
        ->assertSee('Product Categories')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $categories = ProductCategory::factory()->count(2)->create();

    $component = Livewire::test(ProductCategoriesIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $categories[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('toggles a category id in and out of the selection', function () {
    $category = ProductCategory::factory()->create();

    Livewire::test(ProductCategoriesIndex::class)
        ->call('toggleSelect', $category->id)
        ->assertSet('selectedIds', [$category->id])
        ->call('toggleSelect', $category->id)
        ->assertSet('selectedIds', []);
});

it('shows the bulk action toolbar only once something is selected', function () {
    $category = ProductCategory::factory()->create();

    Livewire::test(ProductCategoriesIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $category->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $category->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(ProductCategoriesIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $category = ProductCategory::factory()->create();

    Livewire::test(ProductCategoriesIndex::class)
        ->call('toggleSelect', $category->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'product-category-bulk-delete');
});

it('deletes every selected category and clears the selection', function () {
    $categories = ProductCategory::factory()->count(3)->create();
    $keep = ProductCategory::factory()->create();

    Livewire::test(ProductCategoriesIndex::class)
        ->call('toggleSelect', $categories[0]->id)
        ->call('toggleSelect', $categories[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 categories deleted successfully')
        ->assertDispatched('close-modal', name: 'product-category-bulk-delete');

    expect(ProductCategory::find($categories[0]->id))->toBeNull()
        ->and(ProductCategory::find($categories[1]->id))->toBeNull()
        ->and(ProductCategory::find($categories[2]->id))->not->toBeNull()
        ->and(ProductCategory::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one category is deleted', function () {
    $category = ProductCategory::factory()->create();

    Livewire::test(ProductCategoriesIndex::class)
        ->call('toggleSelect', $category->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 category deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $categories = ProductCategory::factory()->count(2)->create();

    $component = Livewire::test(ProductCategoriesIndex::class);

    // Nothing selected yet — the edit link is a real, clickable link.
    $component->assertSeeHtml(route('admin.product-categories.edit', $categories[0]->id));

    $component->call('toggleSelect', $categories[0]->id)
        ->assertDontSeeHtml(route('admin.product-categories.edit', $categories[0]->id))
        ->assertDontSeeHtml(route('admin.product-categories.edit', $categories[1]->id));
});
