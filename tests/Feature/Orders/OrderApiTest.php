<?php

use App\Models\Feature;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Transaction;

it('places a cash on delivery order and computes totals server-side', function () {
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
    $response->assertJsonPath('data.payment_method', 'cod');
    $response->assertJsonPath('data.total', 1500);

    $order = Order::sole();
    expect($order->customer_email)->toBe('jane@example.com')
        ->and($order->subtotal)->toEqual('1500.00')
        ->and($order->payment_status)->toBe('pending')
        ->and($order->order_number)->toStartWith('ORD-');

    expect(Transaction::where('order_id', $order->id)->sole()->amount)->toEqual('1500.00');
});

it('never trusts a client-submitted price — always recomputes from the product', function () {
    $product = Product::factory()->published()->create(['price' => 500]);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [
            // A tampered/spoofed price, if it were ever read, would total 1.
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 1],
        ],
    ])->assertCreated();

    expect(Order::sole()->total)->toEqual('500.00');
});

it('rejects an order for an inactive product', function () {
    $product = Product::factory()->draft()->create();

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertJsonValidationErrors(['items.0.product_id']);

    expect(Order::count())->toBe(0);
});

it('rejects a payment method that is not cod and not enabled in settings', function () {
    $product = Product::factory()->published()->create();

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'stripe',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertJsonValidationErrors(['payment_method']);
});

it('accepts a gateway payment method once it is enabled in settings', function () {
    PaymentGateway::create(['code' => 'stripe', 'name' => 'Stripe', 'is_enabled' => true]);
    $product = Product::factory()->published()->create(['price' => 200]);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'stripe',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertCreated();

    expect(Order::sole()->payment_method)->toBe('stripe');
});

it('validates required fields', function () {
    $this->postJson('/api/v1/orders', [])
        ->assertJsonValidationErrors(['customer_name', 'customer_email', 'customer_phone', 'shipping_address', 'payment_method', 'items']);
});

it('looks up an order by number and matching email', function () {
    $product = Product::factory()->published()->create(['price' => 100]);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]);

    $order = Order::sole();

    $this->getJson("/api/v1/orders/{$order->order_number}?email=jane@example.com")
        ->assertOk()
        ->assertJsonPath('data.order_number', $order->order_number);

    $this->getJson("/api/v1/orders/{$order->order_number}?email=someone-else@example.com")
        ->assertNotFound();
});

it('is blocked when the orders feature is disabled', function () {
    Feature::create(['key' => 'orders', 'label' => 'Orders & Reports', 'is_enabled' => false]);

    $this->postJson('/api/v1/orders', [])->assertNotFound();
});
