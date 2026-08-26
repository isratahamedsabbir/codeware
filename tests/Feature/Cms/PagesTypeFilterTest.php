<?php

use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('shows only standalone pages by default', function () {
    Page::factory()->create(['type' => 'page', 'slug' => 'about-standalone']);

    $category = ProductCategory::factory()->create(['slug' => 'gadgets-category']);
    Page::create([
        'user_id' => $this->admin->id, 'category_id' => $category->id, 'type' => 'product_category',
        'title' => ['en' => 'Category page'], 'slug' => $category->slug, 'status' => 'active',
    ]);

    Livewire::test(PagesIndex::class)
        ->assertSee('about-standalone')
        ->assertDontSee('gadgets-category');
});

it('filters by type when a specific type is selected', function () {
    Page::factory()->create(['type' => 'page', 'slug' => 'about-standalone']);

    $category = ProductCategory::factory()->create(['slug' => 'gadgets-category']);
    Page::create([
        'user_id' => $this->admin->id, 'category_id' => $category->id, 'type' => 'product_category',
        'title' => ['en' => 'Category page'], 'slug' => $category->slug, 'status' => 'active',
    ]);

    Livewire::test(PagesIndex::class)
        ->set('typeFilter', 'product_category')
        ->assertDontSee('about-standalone')
        ->assertSee('gadgets-category');
});

it('shows every type when "all" is selected', function () {
    Page::factory()->create(['type' => 'page', 'slug' => 'about-standalone']);

    $category = ProductCategory::factory()->create(['slug' => 'gadgets-category']);
    Page::create([
        'user_id' => $this->admin->id, 'category_id' => $category->id, 'type' => 'product_category',
        'title' => ['en' => 'Category page'], 'slug' => $category->slug, 'status' => 'active',
    ]);

    Livewire::test(PagesIndex::class)
        ->set('typeFilter', 'all')
        ->assertSee('about-standalone')
        ->assertSee('gadgets-category');
});

it('opens the puck editor using the row own type, not always "page"', function () {
    $category = ProductCategory::factory()->create();
    $page = Page::create([
        'user_id' => $this->admin->id, 'category_id' => $category->id, 'type' => 'product_category',
        'title' => ['en' => 'Category page'], 'slug' => $category->slug, 'status' => 'active',
    ]);

    $component = Livewire::test(PagesIndex::class)
        ->set('typeFilter', 'all')
        ->call('openPuckEditor', $page->id);

    $xjs = $component->effects['xjs'] ?? [];

    expect($xjs[0]['expression'])->toContain('\/puck\/edit\/product_category\/'.$page->id);
});
