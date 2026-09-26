<?php

use App\Livewire\Admin\Posts\Form as PostForm;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

// SEO fields (seo_title, seo_description, og_image, ...) were moved off products/posts
// entirely — the paired `pages` row (Product::page()/Post::page()) is the only place they
// live in storage. The product/post/category admin forms load and save these fields
// via App\Concerns\HasSeoFields, writing straight onto the paired Page — there's no separate
// copy on the entity itself, so there's nothing for the two sides to drift out of sync on.
//
// The text half of that section (seo_title, seo_description, og_*, twitter_*) is
// translatable, one value per locale: the Bengali version of a product is served
// to Bengali searchers, and a meta title left in English there is a wasted
// impression. The images and the canonical parts stay single-valued — a URL has
// nothing to translate.

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
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
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
        ->assertSet('seo_title.en', 'Page SEO Title')
        ->assertSet('seo_description.en', 'Page SEO Description')
        ->assertSet('og_image', '/og.png');
});

it('saves edited seo fields from the product admin form onto the paired page', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $product = Product::factory()->create();
    Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'seo_title' => 'Old SEO Title',
    ]);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->set('seo_title.en', 'New SEO Title')
        ->call('save');

    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->firstOrFail();
    expect($page->seo_title)->toBe('New SEO Title');
});

it('saving a product leaves its page\'s seo fields untouched when the admin didn\'t edit them', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $product = Product::factory()->create();
    Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'seo_title' => 'Existing SEO Title',
    ]);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->set('name.en', 'Updated Product Name')
        ->call('save');

    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->firstOrFail();
    expect($page->seo_title)->toBe('Existing SEO Title');
});

// --- The SEO copy is per-locale ---

it('stores a separate seo title per locale and reads back the right one', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $product = Product::factory()->create();
    Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title', 'bn' => 'টাইটেল'], 'status' => 'active',
    ]);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->set('seo_title.en', 'A fine product')
        ->set('seo_title.bn', 'একটি ভালো পণ্য')
        ->set('seo_description.bn', 'ভালো মানের পণ্য।')
        ->call('save');

    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->firstOrFail();

    // Key order in the json payload is not part of the contract, hence toEqual
    // rather than toBe here.
    expect($page->getTranslations('seo_title'))->toEqual([
        'en' => 'A fine product',
        'bn' => 'একটি ভালো পণ্য',
    ])->and($page->getTranslations('seo_description'))->toEqual(['bn' => 'ভালো মানের পণ্য।']);

    // Reading is locale-scoped, so each language gets the copy written for it.
    expect($page->getTranslation('seo_title', 'en'))->toBe('A fine product')
        ->and($page->getTranslation('seo_title', 'bn'))->toBe('একটি ভালো পণ্য');
});

it('falls back to the primary locale until a translation is written', function () {
    // A half-translated catalogue is the normal state of a real one, and an
    // empty Bengali meta title would otherwise ship as a blank <title>.
    $page = Page::create([
        'type' => 'page', 'user_id' => User::factory()->create()->id,
        'title' => ['en' => 'About us', 'bn' => 'আমাদের সম্পর্কে'],
        'seo_title' => ['en' => 'About Codeware'],
    ]);

    expect($page->getTranslation('seo_title', 'en', false))->toBe('About Codeware')
        ->and($page->getTranslation('seo_title', 'bn', false))->toBeEmpty()
        ->and($page->getTranslation('seo_title', 'bn'))->toBe('About Codeware');
});

it('writes NULL, not empty json, when every locale is cleared', function () {
    // spatie stores a cleared translatable field as `[]` or `{"en":null}`. A
    // `whereNull()` on seo_title, or an admin screen asking whether this page
    // was ever given a meta title, both need a real NULL to mean anything.
    $page = Page::create([
        'type' => 'page', 'user_id' => User::factory()->create()->id,
        'title' => ['en' => 'About us'],
        'seo_title' => ['en' => 'About Codeware'],
    ]);

    $page->update(['seo_title' => ['en' => '', 'bn' => '']]);
    $page->refresh();

    expect($page->getRawOriginal('seo_title'))->toBeNull()
        ->and(Page::whereNull('seo_title')->whereKey($page->id)->exists())->toBeTrue()
        ->and($page->seo_title)->toBeNull();
});

it('keeps a translation the admin left alone when saving another locale', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $product = Product::factory()->create();
    Page::create([
        'type' => 'product', 'product_id' => $product->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'seo_title' => ['en' => 'English title', 'bn' => 'বাংলা শিরোনাম'],
    ]);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->set('seo_title.en', 'Corrected English title')
        ->call('save');

    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->firstOrFail();

    expect($page->getTranslation('seo_title', 'en'))->toBe('Corrected English title')
        ->and($page->getTranslation('seo_title', 'bn'))->toBe('বাংলা শিরোনাম');
});

it('loads seo fields from the paired page on the post admin form', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
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
        ->assertSet('seo_title.en', 'Post Page SEO Title')
        ->assertSet('seo_description.en', 'Post Page SEO Description')
        ->assertSet('og_image', '/post-og.png');
});

it('saves edited seo fields from the post admin form onto the paired page', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $post = Post::factory()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'seo_title' => 'Old Post SEO Title',
    ]);

    Livewire::test(PostForm::class, ['id' => $post->id])
        ->set('seo_title.en', 'New Post SEO Title')
        ->call('save');

    $page = Page::where(['type' => 'post', 'post_id' => $post->id])->firstOrFail();
    expect($page->seo_title)->toBe('New Post SEO Title');
});

it('saving a post leaves its page\'s seo fields untouched when the admin didn\'t edit them', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $post = Post::factory()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'status' => 'active',
        'seo_title' => 'Existing Post SEO Title',
    ]);

    Livewire::test(PostForm::class, ['id' => $post->id])
        ->set('title.en', 'Updated Post Title')
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
        ->assertJsonPath('data.page.meta_data.seo_title', 'API SEO Title')
        ->assertJsonPath('data.page.meta_data.seo_description', 'API SEO Description')
        ->assertJsonPath('data.page.meta_data.og_image', '/api-og.png');
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
        ->assertJsonPath('data.page.meta_data.seo_title', 'Post API SEO Title')
        ->assertJsonPath('data.page.meta_data.seo_description', 'Post API SEO Description')
        ->assertJsonPath('data.page.meta_data.og_image', '/post-api-og.png');
});

// --- Admin REST API ---

it('admin product API syncs seo_title/seo_description/og_image to a page on create', function () {
    $this->seed(RolePermissionSeeder::class);
    Sanctum::actingAs(User::factory()->admin()->create());

    $response = $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'API SEO Product', 'bn' => ''],
        'status' => 'inactive',
        'og_image' => '/created-og.png',
        'seo_title' => 'Created SEO Title',
        'seo_description' => 'Created SEO Description',
    ])->assertCreated();

    $page = Page::where(['type' => 'product', 'slug' => 'api-seo-product'])->firstOrFail();

    expect($page->seo_title)->toBe('Created SEO Title')
        ->and($page->og_image)->toBe('/created-og.png');
});

it('admin product API syncs seo fields to the existing page on update, keeping the page title in sync with the product', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
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
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
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
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
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
