<?php

use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\ProductAttribute;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('pushes the product discount onto every existing variation automatically', function () {
    $component = Livewire::test(ProductForm::class)
        ->set('name.en', 'Discount Product')
        ->set('price', '1000')
        ->set('variations', [
            ['attributes' => ['Color' => 'Red'], 'sku' => 'SHIRT-RED', 'price' => '1000', 'discount_price' => '', 'quantity' => '5', 'visible' => true, 'image' => '', 'note' => ''],
            ['attributes' => ['Color' => 'Blue'], 'sku' => 'SHIRT-BLUE', 'price' => '1000', 'discount_price' => '', 'quantity' => '5', 'visible' => true, 'image' => '', 'note' => ''],
        ])
        ->set('discount_price', '850');

    $variations = $component->get('variations');

    expect($variations)->toHaveCount(2)
        ->and($variations[0]['discount_price'])->toBe('850')
        ->and($variations[1]['discount_price'])->toBe('850');
});

it('applies the product discount to variations generated afterwards', function () {
    ProductAttribute::create(['name' => 'Color', 'values' => ['Red']]);

    $component = Livewire::test(ProductForm::class)
        ->set('name.en', 'Generated Discount')
        ->set('price', '1000')
        ->set('discount_price', '850')
        ->set('variationActiveAttributes', ['Color'])
        ->set('variationSelectedValues.Color', ['Red']);

    $variations = $component->get('variations');

    expect($variations)->toHaveCount(1)
        ->and($variations[0]['discount_price'])->toBe('850');
});

it('clears every variation discount when the product discount is cleared', function () {
    $component = Livewire::test(ProductForm::class)
        ->set('name.en', 'Cleared Discount')
        ->set('price', '1000')
        ->set('variations', [
            ['attributes' => ['Color' => 'Red'], 'sku' => 'SHIRT-RED', 'price' => '1000', 'discount_price' => '850', 'quantity' => '5', 'visible' => true, 'image' => '', 'note' => ''],
        ])
        ->set('discount_price', '850')
        ->set('discount_price', '');

    expect($component->get('variations.0.discount_price'))->toBe('');
});

it('keeps a manual per-variation override until the base discount changes again', function () {
    $component = Livewire::test(ProductForm::class)
        ->set('name.en', 'Per Variant Discount')
        ->set('price', '1000')
        ->set('variations', [
            ['attributes' => ['Color' => 'Red'], 'sku' => 'SHIRT-RED', 'price' => '1000', 'discount_price' => '850', 'quantity' => '5', 'visible' => true, 'image' => '', 'note' => ''],
            ['attributes' => ['Color' => 'Blue'], 'sku' => 'SHIRT-BLUE', 'price' => '1000', 'discount_price' => '850', 'quantity' => '5', 'visible' => true, 'image' => '', 'note' => ''],
        ])
        ->set('variations.0.discount_price', '700');

    $variations = $component->get('variations');

    expect($variations[0]['discount_price'])->toBe('700')
        ->and($variations[1]['discount_price'])->toBe('850');
});
