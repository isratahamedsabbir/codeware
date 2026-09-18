<?php

use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('creates a product with a warranty period', function () {
    Livewire::test(ProductForm::class)
        ->set('name.en', 'Warranted Product')
        ->set('price', '1000')
        ->set('warranty_months', '12')
        ->call('save');

    $product = Product::whereJsonContains('name->en', 'Warranted Product')->firstOrFail();
    expect($product->warranty_months)->toBe(12)
        ->and($product->hasWarranty())->toBeTrue();
});

it('leaves a product without warranty when the field is left blank', function () {
    Livewire::test(ProductForm::class)
        ->set('name.en', 'Plain Product')
        ->set('price', '1000')
        ->call('save');

    $product = Product::whereJsonContains('name->en', 'Plain Product')->firstOrFail();
    expect($product->warranty_months)->toBeNull()
        ->and($product->hasWarranty())->toBeFalse();
});

it('hydrates the warranty field when editing an existing product', function () {
    $product = Product::factory()->create(['warranty_months' => 24]);

    Livewire::test(ProductForm::class, ['id' => $product->id])
        ->assertSet('warranty_months', '24');
});

it('rejects a negative warranty period', function () {
    Livewire::test(ProductForm::class)
        ->set('name.en', 'Bad Warranty Product')
        ->set('price', '1000')
        ->set('warranty_months', '-5')
        ->call('save')
        ->assertHasErrors(['warranty_months']);
});

it('exposes warranty_months on the public product api', function () {
    $product = Product::factory()->published()->create(['warranty_months' => 6]);
    pairPageFor($product, 'product', 'warranted-product', $this->admin->id);

    $this->getJson('/api/v1/products/warranted-product')
        ->assertJsonPath('data.warranty_months', 6);
});
