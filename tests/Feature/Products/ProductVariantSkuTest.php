<?php

use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use App\Services\OrderPlacement;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('auto-generates a per-combination sku from the base sku and option values', function () {
    ProductAttribute::create(['name' => 'Color', 'values' => ['Red', 'Blue']]);
    ProductAttribute::create(['name' => 'Size', 'values' => ['Small', 'Large']]);

    $component = Livewire::test(ProductForm::class)
        ->set('name.en', 'Variant Sku Product')
        ->set('price', '1000')
        ->set('sku', 'TSHIRT')
        ->set('variationActiveAttributes', ['Color', 'Size'])
        ->set('variationSelectedValues.Color', ['Red', 'Blue'])
        ->set('variationSelectedValues.Size', ['Small']);

    $variations = $component->get('variations');

    expect($variations)->toHaveCount(2)
        ->and($variations[0]['sku'])->toBe('TSHIRT-RED-SMALL')
        ->and($variations[1]['sku'])->toBe('TSHIRT-BLUE-SMALL');
});

it('leaves the combination sku blank until a base sku exists', function () {
    ProductAttribute::create(['name' => 'Color', 'values' => ['Red']]);

    $component = Livewire::test(ProductForm::class)
        ->set('name.en', 'No Sku Yet')
        ->set('price', '1000')
        ->set('variationActiveAttributes', ['Color'])
        ->set('variationSelectedValues.Color', ['Red']);

    $variations = $component->get('variations');

    expect($variations[0]['sku'])->toBe('');
});

it('persists each combination sku and auto-fills a blank one on save', function () {
    Livewire::test(ProductForm::class)
        ->set('name.en', 'Saved Variant Sku')
        ->set('price', '1000')
        ->set('sku', 'JACKET')
        ->set('variations', [
            ['attributes' => ['Color' => 'Red'], 'sku' => '', 'price' => '', 'discount_price' => '', 'quantity' => '1', 'visible' => true, 'image' => '', 'note' => ''],
            ['attributes' => ['Color' => 'Blue'], 'sku' => 'JACKET-BLUE-CUSTOM', 'price' => '', 'discount_price' => '', 'quantity' => '1', 'visible' => true, 'image' => '', 'note' => ''],
        ])
        ->call('save');

    $product = Product::whereJsonContains('name->en', 'Saved Variant Sku')->firstOrFail();

    expect($product->variations)->toHaveCount(2)
        ->and($product->variations[0]['sku'])->toBe('JACKET-RED')
        ->and($product->variations[1]['sku'])->toBe('JACKET-BLUE-CUSTOM');
});

it('rejects duplicate skus across a product\'s combinations', function () {
    Livewire::test(ProductForm::class)
        ->set('name.en', 'Duplicate Variant Sku')
        ->set('price', '1000')
        ->set('sku', 'SHIRT')
        ->set('variations', [
            ['attributes' => ['Color' => 'Red'], 'sku' => 'SHIRT-RED', 'price' => '', 'discount_price' => '', 'quantity' => '1', 'visible' => true, 'image' => '', 'note' => ''],
            ['attributes' => ['Color' => 'Blue'], 'sku' => 'SHIRT-RED', 'price' => '', 'discount_price' => '', 'quantity' => '1', 'visible' => true, 'image' => '', 'note' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['variations.1.sku']);

    expect(Product::whereJsonContains('name->en', 'Duplicate Variant Sku')->count())->toBe(0);
});

it('rejects a combination sku that equals the product sku', function () {
    Livewire::test(ProductForm::class)
        ->set('name.en', 'Parent Sku Clash')
        ->set('price', '1000')
        ->set('sku', 'SHIRT')
        ->set('variations', [
            ['attributes' => ['Color' => 'Red'], 'sku' => 'SHIRT', 'price' => '', 'discount_price' => '', 'quantity' => '1', 'visible' => true, 'image' => '', 'note' => ''],
        ])
        ->call('save')
        ->assertHasErrors(['variations.0.sku']);
});

it('exposes each combination sku on the public product api', function () {
    $product = Product::factory()->published()->create([
        'variations' => [
            ['attributes' => ['Color' => 'Red'], 'sku' => 'TSHIRT-RED', 'price' => 25, 'discount_price' => null, 'quantity' => 5, 'visible' => true],
            ['attributes' => ['Color' => 'Blue'], 'sku' => 'TSHIRT-BLUE', 'price' => 25, 'discount_price' => null, 'quantity' => 5, 'visible' => true],
        ],
    ]);
    pairPageFor($product, 'product', 'variant-sku-api', $this->admin->id);

    $this->getJson('/api/v1/products/variant-sku-api')
        ->assertOk()
        ->assertJsonPath('data.variations.0.sku', 'TSHIRT-RED')
        ->assertJsonPath('data.variations.1.sku', 'TSHIRT-BLUE');
});

it('snapshots the combination sku onto a variant order line', function () {
    $product = Product::factory()->published()->create([
        'quantity' => 10,
        'price' => 20,
        'sku' => 'TSHIRT',
        'variations' => [
            ['attributes' => ['Color' => 'Red', 'Size' => 'M'], 'sku' => 'TSHIRT-RED-M', 'price' => 22, 'discount_price' => null, 'quantity' => 5, 'visible' => true],
        ],
    ]);

    $customer = [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
    ];

    $variantOrder = app(OrderPlacement::class)->placeProducts([
        ['product_id' => $product->id, 'quantity' => 1, 'attributes' => ['Color' => 'Red', 'Size' => 'M']],
    ], $customer);

    expect($variantOrder->items->sole()->sku)->toBe('TSHIRT-RED-M');

    $baseOrder = app(OrderPlacement::class)->placeProducts([
        ['product_id' => $product->id, 'quantity' => 1],
    ], $customer);

    expect($baseOrder->items->sole()->sku)->toBe('TSHIRT');
});
