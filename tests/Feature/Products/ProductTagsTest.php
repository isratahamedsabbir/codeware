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
    expect($product->tags()->pluck('tags.id')->all())->toEqualCanonicalizing($tags->pluck('id')->all());
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

    expect($product->tags()->pluck('tags.id')->all())->toBe([$newTag->id]);
});

it('shares the same tag between a post and a product — one tag pool for both', function () {
    $tag = Tag::factory()->create(['name' => ['en' => 'Organic', 'bn' => '']]);
    $post = Post::factory()->create();
    $product = Product::factory()->create();

    $post->tags()->attach($tag);
    $product->tags()->attach($tag);

    expect($tag->posts()->pluck('posts.id')->all())->toBe([$post->id])
        ->and($tag->products()->pluck('products.id')->all())->toBe([$product->id]);
});
