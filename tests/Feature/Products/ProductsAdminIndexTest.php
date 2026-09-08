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
        'product_category_id' => ProductCategory::factory(),
        'name' => ['en' => 'Wireless Mouse', 'bn' => ''],
    ]);

    Livewire::test(ProductsIndex::class)
        ->call('viewDetails', $product->id)
        ->assertSet('viewingId', $product->id)
        ->assertSee('Wireless Mouse')
        ->call('closeDetails')
        ->assertSet('viewingId', null);
});
