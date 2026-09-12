<?php

use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('opens and closes the view details modal for a product', function () {
    $product = Product::factory()->create([
        'name' => ['en' => 'Wireless Mouse', 'bn' => ''],
    ]);
    $product->categories()->attach(ProductCategory::factory()->create());

    Livewire::test(ProductsIndex::class)
        ->call('viewDetails', $product->id)
        ->assertSet('viewingId', $product->id)
        ->assertSee('Wireless Mouse')
        ->call('closeDetails')
        ->assertSet('viewingId', null);
});

it('toggles a product\'s upcoming status', function () {
    $product = Product::factory()->create(['is_upcoming' => false]);

    Livewire::test(ProductsIndex::class)->call('toggleUpcoming', $product->id);
    expect($product->refresh()->is_upcoming)->toBeTrue();

    Livewire::test(ProductsIndex::class)->call('toggleUpcoming', $product->id);
    expect($product->refresh()->is_upcoming)->toBeFalse();
});
