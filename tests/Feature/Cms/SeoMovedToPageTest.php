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

// SEO fields (seo_title, seo_description, og_image, ...) were moved off products/posts
// entirely — the paired `pages` row (Product::page()/Post::page()) is the only place they
// live in storage. These fields are single-language (not translatable) — meta data doesn't
// need a per-locale value. The product/post/category admin forms load and save these fields
// via App\Concerns\HasSeoFields, writing straight onto the paired Page — there's no separate
// copy on the entity itself, so there's nothing for the two sides to drift out of sync on.

it('no longer has seo columns on products or posts', function () {
    expect(Schema::hasColumn('products', 'seo_title'))->toBeFalse()
        ->and(Schema::hasColumn('products', 'seo_description'))->toBeFalse()
        ->and(Schema::hasColumn('products', 'og_image'))->toBeFalse()
        ->and(Schema::hasColumn('posts', 'seo_title'))->toBeFalse()
        ->and(Schema::hasColumn('posts', 'seo_description'))->toBeFalse()
        ->and(Schema::hasColumn('posts', 'og_image'))->toBeFalse();
});

// --- Livewire admin forms load and save SEO on the paired page ---

it('loads seo fields from the paired page on the product admin form', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $product = Product::factory()->create();
    Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'og_image' => '/og.png',
        'seo_title' => 'Page SEO Title',
        'seo_description' => 'Page SEO Description',
    ]);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->assertSet('pageId', Page::where(['type' => 'product', 'product_id' => $product->id])->value('id'))
        ->assertSet('seo_title', 'Page SEO Title')
        ->assertSet('seo_description', 'Page SEO Description')
        ->assertSet('og_image', '/og.png');
});

it('saves edited seo fields from the product admin form onto the paired page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $product = Product::factory()->create();
    Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'seo_title' => 'Old SEO Title',
    ]);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->set('seo_title', 'New SEO Title')
        ->call('save');

    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->firstOrFail();
    expect($page->seo_title)->toBe('New SEO Title');
});

it('saving a product leaves its page\'s seo fields untouched when the admin didn\'t edit them', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $product = Product::factory()->create();
    Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'seo_title' => 'Existing SEO Title',
    ]);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->set('name_en', 'Updated Product Name')
        ->call('save');

    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->firstOrFail();
    expect($page->seo_title)->toBe('Existing SEO Title');
});

it('loads seo fields from the paired page on the post admin form', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $post = Post::factory()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'og_image' => '/post-og.png',
        'seo_title' => 'Post Page SEO Title',
        'seo_description' => 'Post Page SEO Description',
    ]);

    Livewire::test(PostForm::class, ['id' => $post->id])
        ->assertSet('pageId', Page::where(['type' => 'post', 'post_id' => $post->id])->value('id'))
        ->assertSet('seo_title', 'Post Page SEO Title')
        ->assertSet('seo_description', 'Post Page SEO Description')
        ->assertSet('og_image', '/post-og.png');
});

it('saves edited seo fields from the post admin form onto the paired page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $post = Post::factory()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'seo_title' => 'Old Post SEO Title',
    ]);

    Livewire::test(PostForm::class, ['id' => $post->id])
        ->set('seo_title', 'New Post SEO Title')
        ->call('save');

    $page = Page::where(['type' => 'post', 'post_id' => $post->id])->firstOrFail();
    expect($page->seo_title)->toBe('New Post SEO Title');
});

it('saving a post leaves its page\'s seo fields untouched when the admin didn\'t edit them', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $post = Post::factory()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'seo_title' => 'Existing Post SEO Title',
    ]);

    Livewire::test(PostForm::class, ['id' => $post->id])
        ->set('title_en', 'Updated Post Title')
        ->call('save');

    $page = Page::where(['type' => 'post', 'post_id' => $post->id])->firstOrFail();
    expect($page->seo_title)->toBe('Existing Post SEO Title');
});

// --- Public API ---

it('public product API reads SEO fields from the page', function () {
    $user = User::factory()->create();
    $product = Product::factory()->published()->create();
    Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $user->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'og_image' => '/api-og.png',
        'seo_title' => 'API SEO Title',
        'seo_description' => 'API SEO Description',
    ]);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.page.seo_title', 'API SEO Title')
        ->assertJsonPath('data.page.seo_description', 'API SEO Description')
        ->assertJsonPath('data.page.og_image', '/api-og.png');
});

it('public post API reads SEO fields from the page', function () {
    $user = User::factory()->create();
    $post = Post::factory()->published()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => $user->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'og_image' => '/post-api-og.png',
        'seo_title' => 'Post API SEO Title',
        'seo_description' => 'Post API SEO Description',
    ]);

    $this->getJson("/api/v1/posts/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.page.seo_title', 'Post API SEO Title')
        ->assertJsonPath('data.page.seo_description', 'Post API SEO Description')
        ->assertJsonPath('data.page.og_image', '/post-api-og.png');
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

    $page = Page::where(['type' => 'product', 'slug' => 'api_seo_product'])->firstOrFail();

    expect($page->seo_title)->toBe('Created SEO Title')
        ->and($page->og_image)->toBe('/created-og.png');
});

it('admin product API syncs seo fields to the existing page on update, keeping the page title in sync with the product', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Sanctum::actingAs($admin);

    $product = Product::factory()->create();
    $page = Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Stale Title'], 'status' => 'active',
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
        'title' => ['en' => 'Title'], 'status' => 'active',
        'og_image' => '/show-og.png',
        'seo_title' => 'Show SEO Title',
    ]);

    $this->getJson("/api/v1/admin/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('data.og_image', '/show-og.png');
});
