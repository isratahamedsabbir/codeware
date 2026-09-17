<?php

use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Post;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('attaches tags to a product created from the admin form', function () {
    $tags = Tag::factory()->count(2)->create();

    Livewire::test(ProductForm::class)
        ->set('name.en', 'Tagged Product')
        ->set('price', '100')
        ->set('tag_ids', $tags->pluck('id')->all())
        ->call('save');

    $product = Product::whereJsonContains('name->en', 'Tagged Product')->firstOrFail();
    expect($product->tags()->pluck('categories.id')->all())->toEqualCanonicalizing($tags->pluck('id')->all());
});

it('hydrates the tag checkboxes when editing an existing product', function () {
    $tag = Tag::factory()->create();
    $product = Product::factory()->create();
    $product->tags()->attach($tag);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->assertSet('tag_ids', [$tag->id]);
});

it('updates a product\'s tags on save, replacing the previous set', function () {
    $oldTag = Tag::factory()->create();
    $newTag = Tag::factory()->create();
    $product = Product::factory()->create();
    $product->tags()->attach($oldTag);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->set('tag_ids', [$newTag->id])
        ->call('save');

    expect($product->tags()->pluck('categories.id')->all())->toBe([$newTag->id]);
});

it('shares a legacy tag between a post and a product', function () {
    $tag = Tag::factory()->create(['name' => ['en' => 'Organic', 'bn' => '']]);
    $post = Post::factory()->create();
    $product = Product::factory()->create();

    $post->tags()->attach($tag);
    $product->tags()->attach($tag);

    expect($tag->posts()->pluck('posts.id')->all())->toBe([$post->id])
        ->and($tag->products()->pluck('products.id')->all())->toBe([$product->id]);
});

it('lists both post-typed and product-typed tags in the product form, but not legacy tags', function () {
    Tag::factory()->post()->create(['name' => ['en' => 'Post Tag', 'bn' => '']]);
    Tag::factory()->product()->create(['name' => ['en' => 'Product Tag', 'bn' => '']]);
    Tag::factory()->create(['name' => ['en' => 'Legacy Only', 'bn' => '']]);

    Livewire::test(ProductForm::class)
        ->assertSee('Post Tag')
        ->assertSee('Product Tag')
        ->assertDontSee('Legacy Only');
});

it('creates a product-typed tag inline from the form and selects it', function () {
    Livewire::test(ProductForm::class)
        ->set('newTagName', 'Handmade')
        ->call('createTag')
        ->assertSet('newTagName', '');

    $tag = Tag::whereJsonContains('name->en', 'Handmade')->firstOrFail();

    expect($tag->type)->toBe(Tag::TYPE_PRODUCT);
});
