<?php

use App\Livewire\Admin\PostCategories\Index as PostCategoriesIndex;
use App\Models\PostCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $category = PostCategory::factory()->create();

    Livewire::test(PostCategoriesIndex::class)
        ->call('toggleSelect', $category->id)
        ->assertSee('Blog')
        ->assertSee('Post Categories')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $categories = PostCategory::factory()->count(2)->create();

    $component = Livewire::test(PostCategoriesIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $categories[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $category = PostCategory::factory()->create();

    Livewire::test(PostCategoriesIndex::class)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (')
        ->call('toggleSelect', $category->id)
        ->assertSee('Delete (1)')
        ->assertSee('Export (1)')
        ->call('toggleSelect', $category->id)
        ->assertDontSee('Delete (')
        ->assertDontSee('Export (');
});

it('toggles a category id in and out of the selection', function () {
    $category = PostCategory::factory()->create();

    Livewire::test(PostCategoriesIndex::class)
        ->call('toggleSelect', $category->id)
        ->assertSet('selectedIds', [$category->id])
        ->call('toggleSelect', $category->id)
        ->assertSet('selectedIds', []);
});

it('does not open the bulk delete modal with nothing selected', function () {
    Livewire::test(PostCategoriesIndex::class)
        ->call('confirmBulkDelete')
        ->assertNotDispatched('open-modal');
});

it('opens the bulk delete confirmation modal once something is selected', function () {
    $category = PostCategory::factory()->create();

    Livewire::test(PostCategoriesIndex::class)
        ->call('toggleSelect', $category->id)
        ->call('confirmBulkDelete')
        ->assertDispatched('open-modal', name: 'post-category-bulk-delete');
});

it('deletes every selected category and clears the selection', function () {
    $categories = PostCategory::factory()->count(3)->create();
    $keep = PostCategory::factory()->create();

    Livewire::test(PostCategoriesIndex::class)
        ->call('toggleSelect', $categories[0]->id)
        ->call('toggleSelect', $categories[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 categories deleted successfully')
        ->assertDispatched('close-modal', name: 'post-category-bulk-delete');

    expect(PostCategory::find($categories[0]->id))->toBeNull()
        ->and(PostCategory::find($categories[1]->id))->toBeNull()
        ->and(PostCategory::find($categories[2]->id))->not->toBeNull()
        ->and(PostCategory::find($keep->id))->not->toBeNull();
});

it('uses singular wording when only one category is deleted', function () {
    $category = PostCategory::factory()->create();

    Livewire::test(PostCategoriesIndex::class)
        ->call('toggleSelect', $category->id)
        ->call('bulkDelete')
        ->assertDispatched('notify', message: '1 category deleted successfully');
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $categories = PostCategory::factory()->count(2)->create();

    $component = Livewire::test(PostCategoriesIndex::class);

    $component->assertSeeHtml(route('admin.post-categories.edit', $categories[0]->id));

    $component->call('toggleSelect', $categories[0]->id)
        ->assertDontSeeHtml(route('admin.post-categories.edit', $categories[0]->id))
        ->assertDontSeeHtml(route('admin.post-categories.edit', $categories[1]->id));
});

it('exports only the requested category ids as a downloadable csv', function () {
    $included = PostCategory::factory()->create(['name' => ['en' => 'Included Category', 'bn' => '']]);
    $excluded = PostCategory::factory()->create(['name' => ['en' => 'Excluded Category', 'bn' => '']]);

    $response = $this->get(route('admin.post-categories.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included Category')
        ->and($csv)->not->toContain('Excluded Category');
});
