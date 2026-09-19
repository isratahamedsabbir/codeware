<?php

use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Models\Page;
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
    $page = Page::factory()->create(['type' => 'page']);

    Livewire::test(PagesIndex::class)
        ->call('toggleSelect', $page->id)
        ->assertSee('Pages')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $pages = Page::factory()->count(2)->create(['type' => 'page']);

    $component = Livewire::test(PagesIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $pages[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the delete and export buttons only once a selection exists', function () {
    $page = Page::factory()->create(['type' => 'page']);

    Livewire::test(PagesIndex::class)
        ->assertDontSee('Export (')
        ->assertDontSee('Delete (')
        ->call('toggleSelect', $page->id)
        ->assertSee('Export (1)')
        ->assertSee('Delete (1)');
});

it('toggles a page id in and out of the selection', function () {
    $page = Page::factory()->create(['type' => 'page']);

    Livewire::test(PagesIndex::class)
        ->call('toggleSelect', $page->id)
        ->assertSet('selectedIds', [$page->id])
        ->call('toggleSelect', $page->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(PagesIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete modal once a selection exists', function () {
    $page = Page::factory()->create(['type' => 'page']);

    Livewire::test(PagesIndex::class)
        ->call('toggleSelect', $page->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'page-bulk-delete');
});

it('bulk deletes the selected pages and clears the selection', function () {
    $toDelete = Page::factory()->count(2)->create(['type' => 'page']);
    $keep = Page::factory()->create(['type' => 'page']);

    Livewire::test(PagesIndex::class)
        ->set('selectedIds', $toDelete->pluck('id')->all())
        ->set('deleteConfirmation', 'delete')
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify')
        ->assertDispatched('close-modal', name: 'page-bulk-delete');

    expect(Page::whereIn('id', $toDelete->pluck('id'))->count())->toBe(0);
    expect(Page::find($keep->id))->not->toBeNull();
});

it('refuses to bulk delete pages unless "delete" is typed into the confirmation field', function () {
    $toDelete = Page::factory()->count(2)->create(['type' => 'page']);

    Livewire::test(PagesIndex::class)
        ->set('selectedIds', $toDelete->pluck('id')->all())
        ->set('deleteConfirmation', 'nope')
        ->call('bulkDelete');

    expect(Page::whereIn('id', $toDelete->pluck('id'))->count())->toBe(2);
});

it('cascades a bulk delete to the linked product, same as a single delete', function () {
    $product = Product::factory()->create();
    $page = Page::create([
        'type' => 'product',
        'product_id' => $product->id,
        'user_id' => $this->admin->id,
        'title' => ['en' => 'Linked product page'],
        'status' => 'active',
    ]);

    Livewire::test(PagesIndex::class)
        ->set('selectedIds', [$page->id])
        ->set('deleteConfirmation', 'delete')
        ->call('bulkDelete');

    expect(Page::find($page->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id)->trashed())->toBeTrue();
});

it('disables the row actions dropdown trigger while a bulk selection is active', function () {
    $page = Page::factory()->create(['type' => 'page']);

    Livewire::test(PagesIndex::class)
        ->call('toggleSelect', $page->id)
        ->assertSeeHtml('disabled');
});
