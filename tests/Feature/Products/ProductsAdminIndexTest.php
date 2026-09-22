<?php

use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
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

it('shows the generated unique code between the name and slug in the datatable', function () {
    $product = Product::factory()->create(['name' => ['en' => 'Wireless Keyboard', 'bn' => '']]);
    Page::factory()->create(['type' => 'product', 'product_id' => $product->id, 'slug' => 'wireless-keyboard', 'status' => 'active']);

    Livewire::test(ProductsIndex::class)
        ->assertSeeInOrder(['Wireless Keyboard', $product->code, 'wireless-keyboard']);
});

it('toggles a product\'s upcoming status', function () {
    $product = Product::factory()->create(['is_upcoming' => false]);

    Livewire::test(ProductsIndex::class)->call('toggleUpcoming', $product->id);
    expect($product->refresh()->is_upcoming)->toBeTrue();

    Livewire::test(ProductsIndex::class)->call('toggleUpcoming', $product->id);
    expect($product->refresh()->is_upcoming)->toBeFalse();
});
