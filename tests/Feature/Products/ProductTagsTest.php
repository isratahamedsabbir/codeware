<?php

use App\Livewire\Admin\Posts\Form as PostForm;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Post;
use App\Models\Product;
use App\Models\Tag;
use App\Models\Type;
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

it('keeps a product tag off a post, since a tag belongs to exactly one pool', function () {
    $tag = Tag::factory()->create(['name' => ['en' => 'Organic', 'bn' => '']]);
    $post = Post::factory()->create();
    $product = Product::factory()->create();

    $post->tags()->attach($tag);
    $product->tags()->attach($tag);

    // The pivot is a plain many-to-many, so the database will happily store a
    // cross-pool link — what stops it is the Post form's validation, which only
    // accepts post-pool tag ids.
    Livewire::test(PostForm::class)
        ->set('tag_ids', [$tag->id])
        ->call('save')
        ->assertHasErrors('tag_ids.0');
});

it('lists only product-typed tags in the product form', function () {
    Tag::factory()->post()->create(['name' => ['en' => 'Post Tag', 'bn' => '']]);
    Tag::factory()->create(['name' => ['en' => 'Product Tag', 'bn' => '']]);

    Livewire::test(ProductForm::class)
        ->assertSee('Product Tag')
        ->assertDontSee('Post Tag');
});

it('creates a product-typed tag inline from the form and selects it', function () {
    Livewire::test(ProductForm::class)
        ->set('newTagName', 'Handmade')
        ->call('createTag')
        ->assertSet('newTagName', '');

    $tag = Tag::whereJsonContains('name->en', 'Handmade')->firstOrFail();

    expect($tag->type_id)->toBe(Type::idFor(Type::PRODUCT));
});
