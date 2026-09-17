<?php

use App\Livewire\Admin\Categories\Index as CategoriesIndex;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $categories = Category::factory()->count(2)->create();

    $component = Livewire::test(CategoriesIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $categories[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows the bulk action toolbar only once something is selected', function () {
    $category = Category::factory()->create();

    Livewire::test(CategoriesIndex::class)
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
    $category = Category::factory()->create();

    Livewire::test(CategoriesIndex::class)
        ->call('toggleSelect', $category->id)
        ->assertSet('selectedIds', [$category->id])
        ->call('toggleSelect', $category->id)
        ->assertSet('selectedIds', []);
});

it('deletes every selected category and clears the selection', function () {
    $categories = Category::factory()->count(3)->create();
    $keep = Category::factory()->create();

    Livewire::test(CategoriesIndex::class)
        ->call('toggleSelect', $categories[0]->id)
        ->call('toggleSelect', $categories[1]->id)
        ->call('bulkDelete')
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: '2 categories deleted successfully');

    expect(Category::find($categories[0]->id))->toBeNull()
        ->and(Category::find($categories[1]->id))->toBeNull()
        ->and(Category::find($categories[2]->id))->not->toBeNull()
        ->and(Category::find($keep->id))->not->toBeNull();
});

it('disables the per-row actions for every row once a bulk selection is active', function () {
    $categories = Category::factory()->count(2)->create();

    $component = Livewire::test(CategoriesIndex::class);

    $component->assertSeeHtml(route('admin.categories.edit', $categories[0]->id));

    $component->call('toggleSelect', $categories[0]->id)
        ->assertDontSeeHtml(route('admin.categories.edit', $categories[0]->id))
        ->assertDontSeeHtml(route('admin.categories.edit', $categories[1]->id));
});
