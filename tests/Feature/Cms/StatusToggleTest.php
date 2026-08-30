<?php

use App\Livewire\Admin\Cms\Index as CmsIndex;
use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Livewire\Admin\PostCategories\Index as PostCategoriesIndex;
use App\Livewire\Admin\Posts\Index as PostsIndex;
use App\Livewire\Admin\ProductCategories\Index as ProductCategoriesIndex;
use App\Livewire\Admin\Products\Form;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Livewire\Admin\Tags\Index as TagsIndex;
use App\Models\CmsSection;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tag;
use App\Models\User;
use Livewire\Exceptions\PublicPropertyNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('toggles a product status and its paired page from the list', function () {
    $product = Product::factory()->create(['status' => 'inactive']);
    $page = Page::create(['user_id' => $this->admin->id, 'product_id' => $product->id, 'type' => 'product', 'title' => $product->name, 'slug' => $product->slug, 'status' => 'inactive']);

    Livewire::test(ProductsIndex::class)->call('toggleStatus', $product->id);

    expect($product->fresh()->status)->toBe('active')
        ->and($page->fresh()->status)->toBe('active');

    Livewire::test(ProductsIndex::class)->call('toggleStatus', $product->id);

    expect($product->fresh()->status)->toBe('inactive');
});

it('toggles a product category status and its paired page from the list', function () {
    $category = ProductCategory::factory()->create(['status' => 'inactive']);
    $page = Page::create(['user_id' => $this->admin->id, 'category_id' => $category->id, 'type' => 'product_category', 'title' => $category->name, 'slug' => $category->slug, 'status' => 'inactive']);

    Livewire::test(ProductCategoriesIndex::class)->call('toggleStatus', $category->id);

    expect($category->fresh()->status)->toBe('active')
        ->and($page->fresh()->status)->toBe('active');
});

it('toggles a post category status and its paired page from the list', function () {
    $category = PostCategory::factory()->create(['status' => 'inactive']);
    $page = Page::create(['user_id' => $this->admin->id, 'category_id' => $category->id, 'type' => 'post_category', 'title' => $category->name, 'slug' => $category->slug, 'status' => 'inactive']);

    Livewire::test(PostCategoriesIndex::class)->call('toggleStatus', $category->id);

    expect($category->fresh()->status)->toBe('active')
        ->and($page->fresh()->status)->toBe('active');
});

it('toggles a post status, sets published_at once, and syncs its paired page from the list', function () {
    $post = Post::factory()->create(['status' => 'inactive', 'published_at' => null]);
    $page = Page::create(['user_id' => $this->admin->id, 'post_id' => $post->id, 'type' => 'post', 'title' => $post->title, 'slug' => $post->slug, 'status' => 'inactive']);

    Livewire::test(PostsIndex::class)->call('toggleStatus', $post->id);

    $post->refresh();
    expect($post->status)->toBe('active')
        ->and($post->published_at)->not->toBeNull()
        ->and($page->fresh()->status)->toBe('active');

    $firstPublishedAt = $post->published_at;

    Livewire::test(PostsIndex::class)->call('toggleStatus', $post->id);
    Livewire::test(PostsIndex::class)->call('toggleStatus', $post->id);

    expect($post->fresh()->published_at->equalTo($firstPublishedAt))->toBeTrue();
});

it('toggles a tag status from the list', function () {
    $tag = Tag::factory()->create(['status' => 'inactive']);

    Livewire::test(TagsIndex::class)->call('toggleStatus', $tag->id);

    expect($tag->fresh()->status)->toBe('active');
});

it('toggles a page status and its linked entity from the list', function () {
    $product = Product::factory()->create(['status' => 'inactive']);
    $page = Page::create(['user_id' => $this->admin->id, 'product_id' => $product->id, 'type' => 'product', 'title' => $product->name, 'slug' => $product->slug, 'status' => 'inactive']);

    Livewire::test(PagesIndex::class)->set('typeFilter', 'all')->call('toggleStatus', $page->id);

    expect($page->fresh()->status)->toBe('active')
        ->and($product->fresh()->status)->toBe('active');
});

it('toggles a cms section status from the list', function () {
    $page = Page::factory()->create();
    $cms = CmsSection::factory()->create(['page_id' => $page->id, 'status' => 'inactive']);

    Livewire::test(CmsIndex::class, ['pageId' => $page->id])->call('toggleStatus', $cms->id);

    expect($cms->fresh()->status)->toBe('active');
});

it('does not expose a status field on the product form', function () {
    expect(fn () => Livewire::test(Form::class)->set('status', 'active'))
        ->toThrow(PublicPropertyNotFoundException::class);
});

it('creates new records inactive by default across resources', function () {
    Livewire::test(App\Livewire\Admin\Tags\Form::class)
        ->set('name_en', 'Fresh Tag')
        ->call('save');

    expect(Tag::where('slug', 'fresh-tag')->sole()->status)->toBe('inactive');
});
