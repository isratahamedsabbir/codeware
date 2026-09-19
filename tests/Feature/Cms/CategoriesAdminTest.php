<?php

use App\Livewire\Admin\Categories\Form as CategoryForm;
use App\Livewire\Admin\Categories\Index as CategoriesIndex;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('renders the categories index, defaulting to the product pool', function () {
    Category::factory()->create(['type' => Category::TYPE_PRODUCT, 'name' => ['en' => 'Fertilizers', 'bn' => '']]);
    Category::factory()->create(['type' => Category::TYPE_POST, 'name' => ['en' => 'Company News', 'bn' => '']]);

    Livewire::test(CategoriesIndex::class)
        ->assertSee('Fertilizers')
        ->assertDontSee('Company News');
});

it('switches to the post pool via the type filter', function () {
    Category::factory()->create(['type' => Category::TYPE_PRODUCT, 'name' => ['en' => 'Fertilizers', 'bn' => '']]);
    Category::factory()->create(['type' => Category::TYPE_POST, 'name' => ['en' => 'Company News', 'bn' => '']]);

    Livewire::test(CategoriesIndex::class)
        ->set('typeFilter', Category::TYPE_POST)
        ->assertSee('Company News')
        ->assertDontSee('Fertilizers');
});

it('creates a product category with an icon and a parent, inactive by default', function () {
    $parent = Category::factory()->create(['type' => Category::TYPE_PRODUCT, 'name' => ['en' => 'Garden', 'bn' => '']]);

    Livewire::test(CategoryForm::class)
        ->set('name.en', 'Fertilizers')
        ->set('icon', '/storage/media/icon.png')
        ->set('parentId', $parent->id)
        ->call('save');

    $category = Category::where('type', Category::TYPE_PRODUCT)->whereJsonContains('name->en', 'Fertilizers')->firstOrFail();
    expect($category->icon)->toBe('/storage/media/icon.png')
        ->and($category->parent_id)->toBe($parent->id)
        ->and($category->status)->toBe('inactive');
});

it('creates a post category with a description, inactive by default', function () {
    Livewire::test(CategoryForm::class)
        ->set('type', Category::TYPE_POST)
        ->set('name.en', 'Company News')
        ->set('description.en', 'Announcements and updates')
        ->call('save');

    $category = Category::where('type', Category::TYPE_POST)->whereJsonContains('name->en', 'Company News')->firstOrFail();
    expect($category->getTranslation('description', 'en', false))->toBe('Announcements and updates')
        ->and($category->parent_id)->toBeNull()
        ->and($category->status)->toBe('inactive');
});

it('hydrates the correct type and fields when editing an existing category', function () {
    $category = Category::factory()->create([
        'type' => Category::TYPE_POST,
        'name' => ['en' => 'Company News', 'bn' => ''],
        'description' => ['en' => 'Old description', 'bn' => ''],
    ]);
    Page::create(['type' => Category::TYPE_POST, 'category_id' => $category->id, 'user_id' => $this->admin->id, 'title' => ['en' => 'Company News'], 'slug' => 'company-news', 'status' => 'inactive']);

    Livewire::test(CategoryForm::class, ['id' => $category->id])
        ->assertSet('type', Category::TYPE_POST)
        ->assertSet('name.en', 'Company News')
        ->assertSet('description.en', 'Old description');
});

it('rejects a slug that collides across types, since categories are now globally unique', function () {
    Livewire::test(CategoryForm::class)
        ->set('name.en', 'Shared Slug')
        ->call('save');

    Livewire::test(CategoryForm::class)
        ->set('type', Category::TYPE_POST)
        ->set('name.en', 'Something Else')
        ->set('slug', 'shared_slug')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('deletes a category and its paired page (hard delete, no SoftDeletes on categories)', function () {
    $category = Category::factory()->create(['type' => Category::TYPE_PRODUCT]);
    $page = Page::create(['type' => Category::TYPE_PRODUCT, 'category_id' => $category->id, 'user_id' => $this->admin->id, 'title' => ['en' => 'X'], 'slug' => 'x', 'status' => 'inactive']);

    Livewire::test(CategoriesIndex::class)
        ->call('confirmDelete', $category->id)
        ->call('delete');

    expect(Category::find($category->id))->toBeNull()
        ->and(Page::find($page->id))->toBeNull();
});

it('toggles a category\'s status from the index', function () {
    $category = Category::factory()->create(['type' => Category::TYPE_PRODUCT, 'status' => 'active']);

    Livewire::test(CategoriesIndex::class)->call('toggleStatus', $category->id);

    expect($category->refresh()->status)->toBe('inactive');
});
