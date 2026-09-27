<?php

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

it('places an order with no per-item type discriminator', function () {
    $product = Product::factory()->published()->create(['price' => 500]);

    $response = $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 3],
        ],
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.total', 1500);
    $response->assertJsonMissingPath('data.items.0.type');

    expect(Order::sole()->items->sole()->product_id)->toBe($product->id);
});

it('requires a shipping address', function () {
    $product = Product::factory()->published()->create();

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertJsonValidationErrors(['shipping_address']);

    expect(Order::count())->toBe(0);
});

it('rejects a service_id, because a service is booked rather than ordered', function () {
    $service = Service::factory()->published()->create();

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [
            ['service_id' => $service->id, 'quantity' => 1],
        ],
    ])->assertJsonValidationErrors(['items.0.product_id']);

    expect(Order::count())->toBe(0);
});

it('no longer exposes the removed per-type order endpoints', function () {
    expect(Route::has('orders.store.products'))->toBeFalse()
        ->and(Route::has('orders.store.services'))->toBeFalse();
});

it('rejects placing an order while the shop is turned off', function () {
    Setting::set('shop_enabled', '0');
    $product = Product::factory()->published()->create();

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertStatus(503);

    expect(Order::count())->toBe(0);
});

it('applies a coupon', function () {
    $product = Product::factory()->published()->create(['price' => 500]);

    Coupon::factory()->active()->create([
        'code' => 'SAVE10',
        'type' => 'percentage',
        'value' => 10,
        'min_order_amount' => null,
        'max_uses' => null,
    ]);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'coupon_code' => 'save10',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.subtotal', 1000)
        ->assertJsonPath('data.discount', 100)
        ->assertJsonPath('data.total', 900);
});

it('never trusts a client-submitted price', function () {
    $product = Product::factory()->published()->create(['price' => 500]);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1],
        ],
    ])->assertCreated();

    expect(Order::sole()->total)->toEqual('500.00');
});
