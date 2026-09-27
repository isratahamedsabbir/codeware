<?php

use App\Livewire\Admin\Categories\Form as CategoryForm;
use App\Livewire\Admin\Categories\Index as CategoriesIndex;
use App\Models\Category;
use App\Models\Page;
use App\Models\Type;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
    $this->productTypeId = Type::idFor(Type::PRODUCT);
    $this->postTypeId = Type::idFor(Type::POST);
});

it('renders the categories index, defaulting to the product pool', function () {
    Category::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => '']]);
    Category::factory()->post()->create(['name' => ['en' => 'Company News', 'bn' => '']]);

    Livewire::test(CategoriesIndex::class)
        ->assertSee('Fertilizers')
        ->assertDontSee('Company News');
});

it('switches to the post pool via the type filter', function () {
    Category::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => '']]);
    Category::factory()->post()->create(['name' => ['en' => 'Company News', 'bn' => '']]);

    Livewire::test(CategoriesIndex::class)
        ->set('typeFilter', $this->postTypeId)
        ->assertSee('Company News')
        ->assertDontSee('Fertilizers');
});

it('creates a product category with an icon and a parent, inactive by default', function () {
    $parent = Category::factory()->create(['name' => ['en' => 'Garden', 'bn' => '']]);

    Livewire::test(CategoryForm::class)
        ->set('name.en', 'Fertilizers')
        ->set('icon', '/storage/media/icon.png')
        ->set('parentId', $parent->id)
        ->call('save');

    $category = Category::where('type_id', $this->productTypeId)->whereJsonContains('name->en', 'Fertilizers')->firstOrFail();
    expect($category->icon)->toBe('/storage/media/icon.png')
        ->and($category->parent_id)->toBe($parent->id)
        ->and($category->status)->toBe('inactive');
});

it('creates a post category with a description, inactive by default', function () {
    Livewire::test(CategoryForm::class)
        ->set('typeId', $this->postTypeId)
        ->set('name.en', 'Company News')
        ->set('description.en', 'Announcements and updates')
        ->call('save');

    $category = Category::where('type_id', $this->postTypeId)->whereJsonContains('name->en', 'Company News')->firstOrFail();
    expect($category->getTranslation('description', 'en', false))->toBe('Announcements and updates')
        ->and($category->parent_id)->toBeNull()
        ->and($category->status)->toBe('inactive');
});

it('refuses to save without a type', function () {
    Livewire::test(CategoryForm::class)
        ->set('typeId', null)
        ->set('name.en', 'Typeless')
        ->call('save')
        ->assertHasErrors('typeId');
});

it('hydrates the correct type and fields when editing an existing category', function () {
    $category = Category::factory()->post()->create([
        'name' => ['en' => 'Company News', 'bn' => ''],
        'description' => ['en' => 'Old description', 'bn' => ''],
    ]);
    Page::create(['type' => 'post_category', 'category_id' => $category->id, 'user_id' => $this->admin->id, 'title' => ['en' => 'Company News'], 'slug' => 'company-news', 'status' => 'inactive']);

    Livewire::test(CategoryForm::class, ['id' => $category->id])
        ->assertSet('typeId', $this->postTypeId)
        ->assertSet('name.en', 'Company News')
        ->assertSet('description.en', 'Old description');
});

it('rejects a slug that collides across types, since categories are now globally unique', function () {
    Livewire::test(CategoryForm::class)
        ->set('name.en', 'Shared Slug')
        ->call('save');

    Livewire::test(CategoryForm::class)
        ->set('typeId', $this->postTypeId)
        ->set('name.en', 'Something Else')
        ->set('slug', 'shared-slug')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('deletes a category and its paired page (hard delete, no SoftDeletes on categories)', function () {
    $category = Category::factory()->create();
    $page = Page::create(['type' => 'product_category', 'category_id' => $category->id, 'user_id' => $this->admin->id, 'title' => ['en' => 'X'], 'slug' => 'x', 'status' => 'inactive']);

    Livewire::test(CategoriesIndex::class)
        ->call('confirmDelete', $category->id)
        ->call('delete');

    expect(Category::find($category->id))->toBeNull()
        ->and(Page::find($page->id))->toBeNull();
});

it('toggles a category\'s status from the index', function () {
    $category = Category::factory()->create(['status' => 'active']);

    Livewire::test(CategoriesIndex::class)->call('toggleStatus', $category->id);

    expect($category->refresh()->status)->toBe('inactive');
});

it('toggles a category\'s featured flag from the index', function () {
    $category = Category::factory()->create();

    Livewire::test(CategoriesIndex::class)->call('toggleFeatured', $category->id);

    expect($category->refresh()->featured)->toBeTrue();

    Livewire::test(CategoriesIndex::class)->call('toggleFeatured', $category->id);

    expect($category->refresh()->featured)->toBeFalse();
});

it('reorders only within the selected pool', function () {
    $first = Category::factory()->create(['sort_order' => 1]);
    $second = Category::factory()->create(['sort_order' => 2]);
    $post = Category::factory()->post()->create(['sort_order' => 9]);

    Livewire::test(CategoriesIndex::class)->call('reorder', [$second->id, $first->id]);

    expect($first->refresh()->sort_order)->toBe(1)
        ->and($second->refresh()->sort_order)->toBe(0)
        // The post-pool row is not in the visible list, so it must not be swept
        // up by the reorder's bulk update.
        ->and($post->refresh()->sort_order)->toBe(9);
});
