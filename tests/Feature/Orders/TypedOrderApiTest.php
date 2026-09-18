<?php

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;

it('places a product-only order without a per-item type discriminator', function () {
    $product = Product::factory()->published()->create(['price' => 500]);

    $response = $this->postJson('/api/v1/orders/products', [
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
    $response->assertJsonPath('data.items.0.type', 'product');

    $item = Order::sole()->items->sole();
    expect($item->product_id)->toBe($product->id)
        ->and($item->service_id)->toBeNull();
});

it('requires a shipping address on the product-only endpoint', function () {
    $product = Product::factory()->published()->create();

    $this->postJson('/api/v1/orders/products', [
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

it('rejects a service_id on the product-only endpoint', function () {
    $service = Service::factory()->published()->create();

    $this->postJson('/api/v1/orders/products', [
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

it('rejects placing a product order while the shop is turned off', function () {
    Setting::set('shop_enabled', '0');
    $product = Product::factory()->published()->create();

    $this->postJson('/api/v1/orders/products', [
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

it('applies a coupon on the product-only endpoint', function () {
    $product = Product::factory()->published()->create(['price' => 500]);

    Coupon::factory()->active()->create([
        'code' => 'SAVE10',
        'type' => 'percentage',
        'value' => 10,
        'min_order_amount' => null,
        'max_uses' => null,
    ]);

    $this->postJson('/api/v1/orders/products', [
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

it('places a service-only order without a shipping address', function () {
    $service = Service::factory()->published()->create(['price' => 800]);

    $response = $this->postJson('/api/v1/orders/services', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'payment_method' => 'cod',
        'items' => [
            ['service_id' => $service->id, 'quantity' => 1],
        ],
    ]);

    $response->assertCreated();
    $response->assertJsonPath('data.total', 800);
    $response->assertJsonPath('data.items.0.type', 'service');

    $order = Order::sole();
    expect($order->shipping_address)->toBeNull();

    $item = $order->items->sole();
    expect($item->service_id)->toBe($service->id)
        ->and($item->product_id)->toBeNull();
});

it('rejects a product_id on the service-only endpoint', function () {
    $product = Product::factory()->published()->create();

    $this->postJson('/api/v1/orders/services', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertJsonValidationErrors(['items.0.service_id']);

    expect(Order::count())->toBe(0);
});

it('rejects an order for an inactive service on the service-only endpoint', function () {
    $service = Service::factory()->draft()->create();

    $this->postJson('/api/v1/orders/services', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'payment_method' => 'cod',
        'items' => [
            ['service_id' => $service->id, 'quantity' => 1],
        ],
    ])->assertJsonValidationErrors(['items.0.service_id']);

    expect(Order::count())->toBe(0);
});

it('never trusts a client-submitted price on either typed endpoint', function () {
    $product = Product::factory()->published()->create(['price' => 500]);
    $service = Service::factory()->published()->create(['price' => 800]);

    $this->postJson('/api/v1/orders/products', [
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

    $this->postJson('/api/v1/orders/services', [
        'customer_name' => 'John Doe',
        'customer_email' => 'john@example.com',
        'customer_phone' => '01712345679',
        'payment_method' => 'cod',
        'items' => [
            ['service_id' => $service->id, 'quantity' => 1, 'unit_price' => 1],
        ],
    ])->assertCreated();

    expect(Order::where('customer_email', 'john@example.com')->sole()->total)->toEqual('800.00');
});
