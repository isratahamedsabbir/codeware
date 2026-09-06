<?php

use App\Livewire\Admin\Pages\Form as PageForm;
use App\Livewire\Admin\PostCategories\Form as PostCategoryForm;
use App\Livewire\Admin\Posts\Form as PostForm;
use App\Livewire\Admin\ProductCategories\Form as ProductCategoryForm;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Support\Slug;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('formats slugs with underscores and strips special characters', function () {
    expect(Slug::make("Men's Shoes!"))->toBe('mens_shoes')
        ->and(Slug::make('Café Menu — Summer 2026'))->toBe('cafe_menu_summer_2026')
        ->and(Slug::make('Hello   World'))->toBe('hello_world');
});

it('live-types the product slug from the name as you type, until manually edited', function () {
    $component = Livewire::test(ProductForm::class)
        ->set('name_en', 'Blue Running Shoes')
        ->assertSet('slug', 'blue_running_shoes');

    // Manually diverging from the auto-generated value stops further auto-updates.
    $component->set('slug', 'custom-shoe-slug')
        ->set('name_en', 'Blue Running Shoes V2')
        ->assertSet('slug', 'custom-shoe-slug');
});

it('does not auto-touch an existing product\'s slug when only its name is edited', function () {
    $product = Product::factory()->create();
    pairPageFor($product, 'product', 'stable_slug', $this->admin->id);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->set('name_en', 'A Brand New Name')
        ->assertSet('slug', 'stable_slug');
});

it('live-types slugs for posts, product categories, and post categories the same way', function () {
    Livewire::test(PostForm::class)
        ->set('title_en', 'My First Blog Post')
        ->assertSet('slug', 'my_first_blog_post');

    Livewire::test(ProductCategoryForm::class)
        ->set('name_en', 'Home Appliances')
        ->assertSet('slug', 'home_appliances');

    Livewire::test(PostCategoryForm::class)
        ->set('name_en', 'Company News')
        ->assertSet('slug', 'company_news');

    Livewire::test(PageForm::class)
        ->set('title_en', 'About Us')
        ->assertSet('slug', 'about_us');
});

it('keeps a product\'s slug and its paired page\'s slug identical after saving', function () {
    Livewire::test(ProductForm::class)
        ->set('name_en', 'Synced Product')
        ->call('save');

    $page = Page::where(['type' => 'product', 'slug' => 'synced_product'])->sole();
    $product = Product::findOrFail($page->product_id);

    expect($product->slug)->toBe($page->slug);
});

it('rejects a product slug that collides with an existing page slug from a different entity', function () {
    $post = Post::factory()->create();
    pairPageFor($post, 'post', 'shared_slug', $this->admin->id);

    Livewire::test(ProductForm::class)
        ->set('name_en', 'Some Product')
        ->set('slug', 'shared_slug')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('rejects a product category slug that collides with a post category, since categories are now globally unique', function () {
    Livewire::test(PostCategoryForm::class)
        ->set('name_en', 'Shared Category')
        ->call('save');

    Livewire::test(ProductCategoryForm::class)
        ->set('name_en', 'Something Else')
        ->set('slug', 'shared_category')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('locks the slug field for a linked page and ignores any edit attempt on save, keeping the product authoritative', function () {
    Livewire::test(ProductForm::class)
        ->set('name_en', 'Editable Product')
        ->call('save');

    $page = Page::where(['type' => 'product', 'slug' => 'editable_product'])->sole();
    $product = Product::findOrFail($page->product_id);

    $component = Livewire::test(PageForm::class, ['id' => $page->id]);
    expect($component->instance()->isLinked())->toBeTrue();

    $component->set('slug', 'renamed_from_page')->call('save');

    expect($product->fresh()->slug)->toBe('editable_product')
        ->and($page->fresh()->slug)->toBe('editable_product');
});

it('locks the slug field for a linked page and ignores any edit attempt on save, keeping the post category authoritative', function () {
    Livewire::test(PostCategoryForm::class)
        ->set('name_en', 'Original Category')
        ->call('save');

    $page = Page::where(['type' => 'post_category', 'slug' => 'original_category'])->sole();
    $category = PostCategory::findOrFail($page->category_id);

    Livewire::test(PageForm::class, ['id' => $page->id])
        ->set('slug', 'renamed_category')
        ->call('save');

    expect($category->fresh()->slug)->toBe('original_category')
        ->and($page->fresh()->slug)->toBe('original_category');
});

it('does not affect other pages when editing a plain (non-typed) page\'s slug', function () {
    Livewire::test(PageForm::class)
        ->set('title_en', 'Plain Page')
        ->call('save');

    $page = Page::where('slug', 'plain_page')->sole();
    expect($page->type)->toBe('page');

    Livewire::test(PageForm::class, ['id' => $page->id])
        ->set('slug', 'renamed_plain_page')
        ->call('save')
        ->assertHasNoErrors();

    expect($page->fresh()->slug)->toBe('renamed_plain_page');
});

it('rejects a page slug that collides with an existing product slug', function () {
    $product = Product::factory()->create();
    pairPageFor($product, 'product', 'taken_slug', $this->admin->id);

    Livewire::test(PageForm::class)
        ->set('title_en', 'New Page')
        ->set('slug', 'taken_slug')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('marks a newly-typed product slug available (green) when it is unique', function () {
    Livewire::test(ProductForm::class)
        ->set('name_en', 'Totally Unique Product')
        ->assertSet('slugAvailable', true);
});

it('marks a product slug unavailable (red) when it collides with another page slug', function () {
    $post = Post::factory()->create();
    pairPageFor($post, 'post', 'taken_by_post', $this->admin->id);

    Livewire::test(ProductForm::class)
        ->set('name_en', 'Some Product')
        ->set('slug', 'taken_by_post')
        ->assertSet('slugAvailable', false);
});

it('re-checks product slug availability on direct manual edits, not just auto-typing', function () {
    $product = Product::factory()->create();
    pairPageFor($product, 'product', 'existing_one', $this->admin->id);

    Livewire::test(ProductForm::class)
        ->set('name_en', 'Fresh Product')
        ->assertSet('slugAvailable', true)
        ->set('slug', 'existing_one')
        ->assertSet('slugAvailable', false)
        ->set('slug', 'existing_one_but_free')
        ->assertSet('slugAvailable', true);
});

it('marks an existing product\'s own unchanged slug as available when editing', function () {
    $product = Product::factory()->create();
    pairPageFor($product, 'product', 'my_own_slug', $this->admin->id);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->assertSet('slugAvailable', true);
});

it('checks slug availability the same way for posts, categories, and pages', function () {
    $existingPost = Post::factory()->create();
    pairPageFor($existingPost, 'post', 'blog_slug_taken', $this->admin->id);

    Livewire::test(PostForm::class)
        ->set('title_en', 'New Blog Post')
        ->set('slug', 'blog_slug_taken')
        ->assertSet('slugAvailable', false);

    Livewire::test(ProductCategoryForm::class)
        ->set('name_en', 'Brand New Category')
        ->assertSet('slugAvailable', true);

    Livewire::test(PostCategoryForm::class)
        ->set('name_en', 'Another Fresh Category')
        ->assertSet('slugAvailable', true);

    Livewire::test(PageForm::class)
        ->set('title_en', 'New Page')
        ->set('slug', 'blog_slug_taken')
        ->assertSet('slugAvailable', false);
});

// --- REST admin API: same slug-sync guarantee as the Livewire forms ---

it('keeps a product\'s page slug in sync when the slug is changed via the REST admin API alone', function () {
    Sanctum::actingAs($this->admin);

    $product = Product::factory()->create();
    pairPageFor($product, 'product', 'old_api_slug', $this->admin->id);

    // Only the slug is sent — no SEO fields — which used to skip the Page sync entirely.
    $this->putJson("/api/v1/admin/products/{$product->id}", ['slug' => 'new_api_slug'])
        ->assertOk();

    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->sole();
    expect($page->slug)->toBe('new_api_slug');
});

it('keeps a post\'s page slug in sync when the slug is changed via the REST admin API alone', function () {
    Sanctum::actingAs($this->admin);

    $post = Post::factory()->create();
    pairPageFor($post, 'post', 'old_post_api_slug', $this->admin->id);

    $this->putJson("/api/v1/admin/posts/{$post->id}", ['slug' => 'new_post_api_slug'])
        ->assertOk();

    $page = Page::where(['type' => 'post', 'post_id' => $post->id])->sole();
    expect($page->slug)->toBe('new_post_api_slug');
});

it('creates a matching page slug when a post is created via the REST admin API', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/posts', ['title' => ['en' => 'API Created Post']])
        ->assertCreated();

    $page = Page::where(['type' => 'post', 'slug' => 'api_created_post'])->sole();

    expect($page->post)->not->toBeNull();
});

it('keeps a product category\'s page slug in sync when edited via the REST admin API alone', function () {
    Sanctum::actingAs($this->admin);

    $category = ProductCategory::factory()->create();
    pairPageFor($category, 'product_category', 'old_cat_api_slug', $this->admin->id);

    $this->putJson("/api/v1/admin/product-categories/{$category->id}", ['slug' => 'new_cat_api_slug'])
        ->assertOk();

    $page = Page::where(['type' => 'product_category', 'category_id' => $category->id])->sole();
    expect($page->slug)->toBe('new_cat_api_slug');
});

it('creates a matching page slug when a product category is created via the REST admin API', function () {
    Sanctum::actingAs($this->admin);

    $this->postJson('/api/v1/admin/product-categories', ['name' => ['en' => 'API Created Category']])
        ->assertCreated();

    $page = Page::where(['type' => 'product_category', 'slug' => 'api_created_category'])->sole();

    expect($page->category)->not->toBeNull();
});
