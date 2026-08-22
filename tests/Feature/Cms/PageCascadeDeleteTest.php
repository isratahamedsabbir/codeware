<?php

use App\Livewire\Admin\Pages\Index as PagesIndex;
use App\Livewire\Admin\PostCategories\Index as PostCategoriesIndex;
use App\Livewire\Admin\Posts\Index as PostsIndex;
use App\Livewire\Admin\ProductCategories\Index as ProductCategoriesIndex;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

// Product, Post, ProductCategory, and PostCategory are each 1:1 paired with a
// Page. Deleting either side of that pair must take the other with it — an
// orphaned Page or an orphaned entity is never useful (see App\Support\PageCascade).

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

function createPageCascadeTestPair(string $type, array $entityAttributes = []): array
{
    $entity = match ($type) {
        'product' => Product::factory()->create($entityAttributes),
        'post' => Post::factory()->create($entityAttributes),
        'product_category' => ProductCategory::factory()->create($entityAttributes),
        'post_category' => PostCategory::factory()->create($entityAttributes),
    };

    $fk = match ($type) {
        'product' => ['product_id' => $entity->id],
        'post' => ['post_id' => $entity->id],
        'product_category', 'post_category' => ['category_id' => $entity->id],
    };

    $page = Page::create([
        'type' => $type,
        ...$fk,
        'user_id' => User::factory()->create()->id,
        'title' => ['en' => 'Title'],
        'slug' => $entity->slug,
        'status' => 'active',
    ]);

    return [$entity, $page];
}

// --- Forward direction: deleting the entity takes its Page with it ---

it('deleting a product via Livewire soft-deletes its paired page', function () {
    [$product, $page] = createPageCascadeTestPair('product');

    Livewire::test(ProductsIndex::class)->call('confirmDelete', $product->id)->call('delete');

    expect(Product::withTrashed()->find($product->id)->trashed())->toBeTrue()
        ->and(Page::withTrashed()->find($page->id)->trashed())->toBeTrue();
});

it('deleting a post via Livewire soft-deletes its paired page', function () {
    [$post, $page] = createPageCascadeTestPair('post');

    Livewire::test(PostsIndex::class)->call('confirmDelete', $post->id)->call('delete');

    expect(Post::withTrashed()->find($post->id)->trashed())->toBeTrue()
        ->and(Page::withTrashed()->find($page->id)->trashed())->toBeTrue();
});

it('deleting a product category via Livewire force-deletes its paired page (no SoftDeletes on categories)', function () {
    [$category, $page] = createPageCascadeTestPair('product_category');

    Livewire::test(ProductCategoriesIndex::class)->call('confirmDelete', $category->id)->call('delete');

    expect(ProductCategory::find($category->id))->toBeNull()
        ->and(Page::withTrashed()->find($page->id))->toBeNull();
});

it('deleting a post category via Livewire force-deletes its paired page', function () {
    [$category, $page] = createPageCascadeTestPair('post_category');

    Livewire::test(PostCategoriesIndex::class)->call('confirmDelete', $category->id)->call('delete');

    expect(PostCategory::find($category->id))->toBeNull()
        ->and(Page::withTrashed()->find($page->id))->toBeNull();
});

it('deleting a product via the admin REST API soft-deletes its paired page', function () {
    Sanctum::actingAs($this->admin);
    [$product, $page] = createPageCascadeTestPair('product');

    $this->deleteJson("/api/v1/admin/products/{$product->id}")->assertNoContent();

    expect(Product::withTrashed()->find($product->id)->trashed())->toBeTrue()
        ->and(Page::withTrashed()->find($page->id)->trashed())->toBeTrue();
});

it('deleting a post via the admin REST API soft-deletes its paired page', function () {
    Sanctum::actingAs($this->admin);
    [$post, $page] = createPageCascadeTestPair('post');

    $this->deleteJson("/api/v1/admin/posts/{$post->id}")->assertNoContent();

    expect(Post::withTrashed()->find($post->id)->trashed())->toBeTrue()
        ->and(Page::withTrashed()->find($page->id)->trashed())->toBeTrue();
});

it('deleting a product category via the admin REST API force-deletes its paired page', function () {
    Sanctum::actingAs($this->admin);
    [$category, $page] = createPageCascadeTestPair('product_category');

    $this->deleteJson("/api/v1/admin/product-categories/{$category->id}")->assertNoContent();

    expect(ProductCategory::find($category->id))->toBeNull()
        ->and(Page::withTrashed()->find($page->id))->toBeNull();
});

// --- Reverse direction: deleting the Page takes its entity with it ---

it('deleting a product-linked page via Livewire also deletes the product', function () {
    [$product, $page] = createPageCascadeTestPair('product');

    Livewire::test(PagesIndex::class)->call('confirmDelete', $page->id)->call('delete');

    expect(Page::withTrashed()->find($page->id)->trashed())->toBeTrue()
        ->and(Product::withTrashed()->find($product->id)->trashed())->toBeTrue();
});

it('deleting a post-linked page via Livewire also deletes the post', function () {
    [$post, $page] = createPageCascadeTestPair('post');

    Livewire::test(PagesIndex::class)->call('confirmDelete', $page->id)->call('delete');

    expect(Page::withTrashed()->find($page->id)->trashed())->toBeTrue()
        ->and(Post::withTrashed()->find($post->id)->trashed())->toBeTrue();
});

it('deleting a product-category-linked page via Livewire also deletes the category', function () {
    [$category, $page] = createPageCascadeTestPair('product_category');

    Livewire::test(PagesIndex::class)->call('confirmDelete', $page->id)->call('delete');

    expect(Page::withTrashed()->find($page->id)->trashed())->toBeTrue()
        ->and(ProductCategory::find($category->id))->toBeNull();
});

it('deleting a post-category-linked page via Livewire also deletes the category', function () {
    [$category, $page] = createPageCascadeTestPair('post_category');

    Livewire::test(PagesIndex::class)->call('confirmDelete', $page->id)->call('delete');

    expect(Page::withTrashed()->find($page->id)->trashed())->toBeTrue()
        ->and(PostCategory::find($category->id))->toBeNull();
});

it('deleting a plain standalone page via Livewire does not error and touches no entity', function () {
    $page = Page::factory()->create(['type' => 'page']);

    Livewire::test(PagesIndex::class)->call('confirmDelete', $page->id)->call('delete');

    expect(Page::withTrashed()->find($page->id)->trashed())->toBeTrue();
});

it('deleting a page via the admin REST API also deletes its linked product', function () {
    Sanctum::actingAs($this->admin);
    [$product, $page] = createPageCascadeTestPair('product');

    $this->deleteJson("/api/v1/admin/pages/{$page->id}")->assertNoContent();

    expect(Page::withTrashed()->find($page->id)->trashed())->toBeTrue()
        ->and(Product::withTrashed()->find($product->id)->trashed())->toBeTrue();
});

it('deleting a page via the admin REST API also deletes its linked post', function () {
    Sanctum::actingAs($this->admin);
    [$post, $page] = createPageCascadeTestPair('post');

    $this->deleteJson("/api/v1/admin/pages/{$page->id}")->assertNoContent();

    expect(Page::withTrashed()->find($page->id)->trashed())->toBeTrue()
        ->and(Post::withTrashed()->find($post->id)->trashed())->toBeTrue();
});

it('deleting a standalone page via the admin REST API works with no linked entity', function () {
    Sanctum::actingAs($this->admin);
    $page = Page::factory()->create(['type' => 'page']);

    $this->deleteJson("/api/v1/admin/pages/{$page->id}")->assertNoContent();

    expect(Page::withTrashed()->find($page->id)->trashed())->toBeTrue();
});
