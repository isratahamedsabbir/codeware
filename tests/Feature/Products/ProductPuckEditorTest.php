<?php

use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('creates a companion page when opening the puck editor for a product that has none', function () {
    $product = Product::factory()->create(['status' => 'active']);

    expect($product->page)->toBeNull();

    Livewire::test(ProductsIndex::class)->call('openPuckEditor', $product->id);

    $page = Page::where(['type' => 'product', 'product_id' => $product->id])->sole();
    expect($page->slug)->toBe($product->slug)
        ->and($page->status)->toBe('active');
});

it('reuses the existing companion page when opening the puck editor for a product', function () {
    $product = Product::factory()->create();
    $page = Page::create([
        'user_id' => $this->admin->id,
        'product_id' => $product->id,
        'type' => 'product',
        'title' => $product->name,
        'slug' => $product->slug,
        'status' => $product->status,
    ]);

    Livewire::test(ProductsIndex::class)->call('openPuckEditor', $product->id);

    expect(Page::where(['type' => 'product', 'product_id' => $product->id])->count())->toBe(1);
    expect(Page::where(['type' => 'product', 'product_id' => $product->id])->sole()->id)->toBe($page->id);
});
