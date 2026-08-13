<?php

use App\Livewire\Admin\Posts\Form as PostForm;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

// SEO fields (seo_title, seo_description, og_image) were moved off products/posts
// entirely — the paired `pages` row (Product::page()/Post::page()) is now the only place
// they live. These fields are single-language (not translatable) — meta data doesn't
// need a per-locale value. These tests cover every read/write path that touches them.

it('no longer has seo columns on products or posts', function () {
    expect(Schema::hasColumn('products', 'seo_title'))->toBeFalse()
        ->and(Schema::hasColumn('products', 'seo_description'))->toBeFalse()
        ->and(Schema::hasColumn('products', 'og_image'))->toBeFalse()
        ->and(Schema::hasColumn('posts', 'seo_title'))->toBeFalse()
        ->and(Schema::hasColumn('posts', 'seo_description'))->toBeFalse()
        ->and(Schema::hasColumn('posts', 'og_image'))->toBeFalse();
});

// --- Livewire admin forms ---

it('loads a product\'s SEO fields from its page when editing', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $product = Product::factory()->create();
    Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'slug' => $product->slug, 'status' => 'active',
        'og_image' => '/og.png',
        'seo_title' => 'Page SEO Title',
        'seo_description' => 'Page SEO Description',
    ]);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->assertSet('seo_title', 'Page SEO Title')
        ->assertSet('seo_description', 'Page SEO Description')
        ->assertSet('og_image', '/og.png');
});

it('saves a product\'s SEO fields to its page, not the product', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(ProductForm::class)
        ->set('name_en', 'New Product')
        ->set('seo_title', 'My SEO Title')
        ->set('seo_description', 'My SEO Description')
        ->set('og_image', '/new-og.png')
        ->call('save');

    $product = Product::where('slug', 'new-product')->firstOrFail();
    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->firstOrFail();

    expect($page->seo_title)->toBe('My SEO Title')
        ->and($page->seo_description)->toBe('My SEO Description')
        ->and($page->og_image)->toBe('/new-og.png');
});

it('loads a post\'s SEO fields from its page when editing', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $post = Post::factory()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'slug' => $post->slug, 'status' => 'active',
        'og_image' => '/post-og.png',
        'seo_title' => 'Post Page SEO Title',
        'seo_description' => 'Post Page SEO Description',
    ]);

    Livewire::test(PostForm::class, ['id' => $post->id])
        ->assertSet('seo_title', 'Post Page SEO Title')
        ->assertSet('seo_description', 'Post Page SEO Description')
        ->assertSet('og_image', '/post-og.png');
});

it('saves a post\'s SEO fields to its page, not the post', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    Livewire::test(PostForm::class)
        ->set('title_en', 'New Post')
        ->set('seo_title', 'Post SEO Title')
        ->set('seo_description', 'Post SEO Description')
        ->set('og_image', '/post-new-og.png')
        ->call('save');

    $post = Post::where('slug', 'new-post')->firstOrFail();
    $page = Page::where(['type' => 'post', 'post_id' => $post->id])->firstOrFail();

    expect($page->seo_title)->toBe('Post SEO Title')
        ->and($page->seo_description)->toBe('Post SEO Description')
        ->and($page->og_image)->toBe('/post-new-og.png');
});

// --- Public API ---

it('public product API reads SEO fields from the page', function () {
    $user = User::factory()->create();
    $product = Product::factory()->published()->create();
    Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $user->id,
        'title' => ['en' => 'Title'], 'slug' => $product->slug, 'status' => 'active',
        'og_image' => '/api-og.png',
        'seo_title' => 'API SEO Title',
        'seo_description' => 'API SEO Description',
    ]);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.seo_title', 'API SEO Title')
        ->assertJsonPath('data.seo_description', 'API SEO Description')
        ->assertJsonPath('data.og_image', '/api-og.png');
});

it('public post API reads SEO fields from the page', function () {
    $user = User::factory()->create();
    $post = Post::factory()->published()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => $user->id,
        'title' => ['en' => 'Title'], 'slug' => $post->slug, 'status' => 'active',
        'og_image' => '/post-api-og.png',
        'seo_title' => 'Post API SEO Title',
        'seo_description' => 'Post API SEO Description',
    ]);

    $this->getJson("/api/v1/posts/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.seo_title', 'Post API SEO Title')
        ->assertJsonPath('data.seo_description', 'Post API SEO Description')
        ->assertJsonPath('data.og_image', '/post-api-og.png');
});

// --- Admin REST API ---

it('admin product API syncs seo_title/seo_description/og_image to a page on create', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => true]));

    $response = $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'API SEO Product', 'bn' => ''],
        'status' => 'inactive',
        'og_image' => '/created-og.png',
        'seo_title' => 'Created SEO Title',
        'seo_description' => 'Created SEO Description',
    ])->assertCreated();

    $product = Product::where('slug', 'api-seo-product')->firstOrFail();
    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->firstOrFail();

    expect($page->seo_title)->toBe('Created SEO Title')
        ->and($page->og_image)->toBe('/created-og.png');
});

it('admin product API syncs seo fields to the existing page on update, keeping the page title in sync with the product', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);

    $product = Product::factory()->create();
    $page = Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Stale Title'], 'slug' => $product->slug, 'status' => 'active',
        'seo_title' => 'Old SEO Title',
    ]);

    $this->putJson("/api/v1/admin/products/{$product->id}", [
        'seo_title' => 'Updated SEO Title',
    ])->assertOk();

    $page->refresh();
    // The sync always re-derives title/slug/status from the product's current row, so a
    // page whose title had drifted stale is self-healed back in step, not left stale.
    expect($page->seo_title)->toBe('Updated SEO Title')
        ->and($page->getTranslation('title', 'en', false))->toBe($product->getTranslation('name', 'en', false));
});

it('admin post API syncs seo fields to a page on update', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);

    $post = Post::factory()->create();

    $this->putJson("/api/v1/admin/posts/{$post->id}", [
        'og_image' => '/post-update-og.png',
        'seo_title' => 'Post Updated SEO Title',
    ])->assertOk();

    $page = Page::where(['type' => 'post', 'post_id' => $post->id])->firstOrFail();
    expect($page->seo_title)->toBe('Post Updated SEO Title')
        ->and($page->og_image)->toBe('/post-update-og.png');
});

it('admin post API show returns SEO fields from the page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);

    $post = Post::factory()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'slug' => $post->slug, 'status' => 'active',
        'og_image' => '/show-og.png',
        'seo_title' => 'Show SEO Title',
    ]);

    $this->getJson("/api/v1/admin/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('data.og_image', '/show-og.png');
});
