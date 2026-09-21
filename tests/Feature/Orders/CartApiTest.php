<?php

use App\Models\Feature;
use App\Models\Product;
use App\Models\User;

it('adds a product to the guest cart', function () {
    $product = Product::factory()->published()->create(['price' => 250]);
    pairPageFor($product, 'product', 'api-cart-item', User::factory()->create()->id);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 2])
        ->assertOk()
        ->assertJsonPath('data.count', 2)
        ->assertJsonPath('data.subtotal', 500)
        ->assertJsonPath('data.items.0.product_id', $product->id)
        ->assertJsonPath('data.items.0.quantity', 2);

    expect(session('cart'))->toBe([$product->id => 2]);
});

it('increments quantity when the same product is added again', function () {
    $product = Product::factory()->published()->create();
    pairPageFor($product, 'product', 'api-cart-twice', User::factory()->create()->id);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 2]);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 3])
        ->assertOk()
        ->assertJsonPath('data.count', 5)
        ->assertJsonPath('data.items.0.quantity', 5);
});

it('rejects adding an inactive or upcoming product to the cart', function () {
    $inactive = Product::factory()->draft()->create();
    $upcoming = Product::factory()->published()->upcoming()->create();

    $this->postJson('/api/v1/cart', ['product_id' => $inactive->id, 'quantity' => 1])
        ->assertJsonValidationErrors(['product_id']);
    $this->postJson('/api/v1/cart', ['product_id' => $upcoming->id, 'quantity' => 1])
        ->assertJsonValidationErrors(['product_id']);

    expect(session('cart', []))->toBe([]);
});

it('rejects a quantity out of range', function () {
    $product = Product::factory()->published()->create();
    pairPageFor($product, 'product', 'api-cart-qty', User::factory()->create()->id);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 0])
        ->assertJsonValidationErrors(['quantity']);
    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 1001])
        ->assertJsonValidationErrors(['quantity']);
});

it('updates a cart line quantity, removing the line at zero', function () {
    $product = Product::factory()->published()->create();
    pairPageFor($product, 'product', 'api-cart-update', User::factory()->create()->id);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 3]);

    $this->putJson("/api/v1/cart/items/{$product->id}", ['quantity' => 7])
        ->assertOk()
        ->assertJsonPath('data.count', 7)
        ->assertJsonPath('data.items.0.quantity', 7);

    $this->putJson("/api/v1/cart/items/{$product->id}", ['quantity' => 0])
        ->assertOk()
        ->assertJsonPath('data.count', 0)
        ->assertJsonPath('data.items', []);
});

it('404s when updating or removing a product that is not in the cart', function () {
    $this->putJson('/api/v1/cart/items/999', ['quantity' => 1])->assertNotFound();
    $this->deleteJson('/api/v1/cart/items/999')->assertNotFound();
});

it('removes a single line or clears the whole cart', function () {
    $a = Product::factory()->published()->create();
    pairPageFor($a, 'product', 'api-cart-a', User::factory()->create()->id);
    $b = Product::factory()->published()->create();
    pairPageFor($b, 'product', 'api-cart-b', User::factory()->create()->id);

    $this->postJson('/api/v1/cart', ['product_id' => $a->id, 'quantity' => 1]);
    $this->postJson('/api/v1/cart', ['product_id' => $b->id, 'quantity' => 2]);

    $this->deleteJson("/api/v1/cart/items/{$a->id}")
        ->assertOk()
        ->assertJsonPath('data.count', 2)
        ->assertJsonPath('data.items.0.product_id', $b->id);

    $this->deleteJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonPath('data.count', 0)
        ->assertJsonPath('data.items', []);
});

it('drops lines whose product has since become inactive', function () {
    $product = Product::factory()->published()->create();
    pairPageFor($product, 'product', 'api-cart-inactive', User::factory()->create()->id);

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 1]);

    $product->update(['status' => 'inactive']);

    $this->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonPath('data.count', 0)
        ->assertJsonPath('data.items', []);
});

it('shows an empty cart for a guest', function () {
    $this->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonPath('data.count', 0)
        ->assertJsonPath('data.items', []);
});

it('is blocked when the orders feature is disabled', function () {
    Feature::create(['key' => 'orders', 'label' => 'Orders & Reports', 'is_enabled' => false]);

    $this->getJson('/api/v1/cart')->assertNotFound();
    $this->postJson('/api/v1/cart', ['product_id' => 1, 'quantity' => 1])->assertNotFound();
});

function variantProduct(string $slug): Product
{
    $product = Product::factory()->published()->create([
        'price' => 20,
        'variations' => [
            ['attributes' => ['Color' => 'Red', 'Size' => 'M'], 'price' => 22, 'discount_price' => 18, 'quantity' => 5, 'visible' => true],
            ['attributes' => ['Color' => 'Blue', 'Size' => 'M'], 'price' => 22, 'discount_price' => null, 'quantity' => 0, 'visible' => true],
        ],
    ]);

    pairPageFor($product, 'product', $slug, User::factory()->create()->id);

    return $product;
}

it('adds a variant combination to the guest cart with its own price', function () {
    $product = variantProduct('api-cart-variant');

    $this->postJson('/api/v1/cart', [
        'product_id' => $product->id,
        'quantity' => 2,
        'attributes' => ['Color' => 'Red', 'Size' => 'M'],
    ])
        ->assertOk()
        ->assertJsonPath('data.count', 2)
        ->assertJsonPath('data.subtotal', 36)
        ->assertJsonPath('data.items.0.attributes', ['Color' => 'Red', 'Size' => 'M'])
        ->assertJsonPath('data.items.0.options_label', 'Color: Red · Size: M')
        ->assertJsonPath('data.items.0.unit_price', 22)
        ->assertJsonPath('data.items.0.discount_price', 18)
        ->assertJsonPath('data.items.0.line_total', 36);

    expect(session('cart'))->toBe(["{$product->id}::Color=Red::Size=M" => 2]);
});

it('keeps the base product and a variant combination as separate lines', function () {
    $product = variantProduct('api-cart-mixed');

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 1]);
    $this->postJson('/api/v1/cart', [
        'product_id' => $product->id,
        'quantity' => 1,
        'attributes' => ['Color' => 'Blue', 'Size' => 'M'],
    ])
        ->assertOk()
        ->assertJsonPath('data.count', 2)
        ->assertJsonPath('data.items.0.attributes', [])
        ->assertJsonPath('data.items.1.options_label', 'Color: Blue · Size: M');
});

it('increments the same variant line when the same combination is added again', function () {
    $product = variantProduct('api-cart-variant-twice');

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 2, 'attributes' => ['Size' => 'M', 'Color' => 'Red']]);
    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 3, 'attributes' => ['Color' => 'Red', 'Size' => 'M']])
        ->assertOk()
        ->assertJsonPath('data.count', 5)
        ->assertJsonPath('data.items.0.quantity', 5);
});

it('rejects a variant combination that does not exist', function () {
    $product = variantProduct('api-cart-bad-combo');

    $this->postJson('/api/v1/cart', [
        'product_id' => $product->id,
        'quantity' => 1,
        'attributes' => ['Color' => 'Red', 'Size' => 'XL'],
    ])->assertJsonValidationErrors(['attributes']);

    expect(session('cart', []))->toBe([]);
});

it('updates and removes a variant line by product id plus attributes', function () {
    $product = variantProduct('api-cart-variant-crud');

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 3, 'attributes' => ['Color' => 'Red', 'Size' => 'M']]);

    $this->putJson("/api/v1/cart/items/{$product->id}", ['quantity' => 6, 'attributes' => ['Color' => 'Red', 'Size' => 'M']])
        ->assertOk()
        ->assertJsonPath('data.count', 6)
        ->assertJsonPath('data.items.0.quantity', 6);

    $this->putJson("/api/v1/cart/items/{$product->id}", ['quantity' => 1, 'attributes' => ['Color' => 'Blue', 'Size' => 'M']])
        ->assertNotFound();

    $this->deleteJson("/api/v1/cart/items/{$product->id}", ['attributes' => ['Color' => 'Red', 'Size' => 'M']])
        ->assertOk()
        ->assertJsonPath('data.count', 0)
        ->assertJsonPath('data.items', []);
});

it('drops a line whose combination has since been hidden by the admin', function () {
    $product = variantProduct('api-cart-variant-hidden');

    $this->postJson('/api/v1/cart', ['product_id' => $product->id, 'quantity' => 1, 'attributes' => ['Color' => 'Red', 'Size' => 'M']]);

    $product->update(['variations' => [
        ['attributes' => ['Color' => 'Red', 'Size' => 'M'], 'price' => 22, 'discount_price' => null, 'quantity' => 5, 'visible' => false],
    ]]);

    $this->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonPath('data.count', 0)
        ->assertJsonPath('data.items', []);
});
