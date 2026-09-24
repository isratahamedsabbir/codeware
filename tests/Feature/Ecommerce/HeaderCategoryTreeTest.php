<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\User;

use function Pest\Laravel\get;

beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
    $this->author = User::factory()->create();
});

function headerCategory(string $name, string $slug, ?int $parentId = null, string $status = 'active'): ProductCategory
{
    $category = ProductCategory::factory()->create([
        'name' => ['en' => $name, 'bn' => ''],
        'parent_id' => $parentId,
        'status' => $status,
    ]);
    pairPageFor($category, 'product_category', $slug, test()->author->id);

    return $category;
}

it('nests child categories under a + toggle in the header dropdown', function () {
    $seeds = headerCategory('Seeds', 'seeds');
    headerCategory('Vegetable Seeds', 'vegetable-seeds', $seeds->id);
    headerCategory('Fertilizers', 'fertilizers');

    $html = get('/')->assertOk()->getContent();

    // The parent carries the expand toggle; the child is listed inside its branch.
    expect($html)->toContain('Show Seeds subcategories')
        ->and($html)->not->toContain('Show Fertilizers subcategories');

    get('/')->assertSeeInOrder(['Show Seeds subcategories', '/category/seeds', 'x-show="expanded"', '/category/vegetable-seeds'], false);
});

it('lifts a child to the top level when its parent is not listed', function () {
    $hidden = headerCategory('Hidden Parent', 'hidden-parent', null, 'inactive');
    headerCategory('Orphan Child', 'orphan-child', $hidden->id);

    get('/')->assertOk()
        ->assertSee('/category/orphan-child', false)
        ->assertDontSee('Show Hidden Parent subcategories');
});
