<?php

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;

beforeEach(function () {
    Setting::set('vat_enabled', '0');
    Setting::set('vat_rate', '0');
});

it('does not add VAT when the vat toggle is off', function () {
    $product = Product::factory()->published()->create(['price' => 1000]);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ])->assertCreated();

    $order = Order::sole();
    expect($order->subtotal)->toEqual('2000.00')
        ->and($order->vat_amount)->toEqual('0.00')
        ->and($order->vat_rate)->toBeNull()
        ->and($order->total)->toEqual('2000.00');
});

it('adds VAT on top of the subtotal when enabled', function () {
    Setting::set('vat_enabled', '1');
    Setting::set('vat_rate', '15');

    $product = Product::factory()->published()->create(['price' => 1000]);

    $response = $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.subtotal', 2000)
        ->assertJsonPath('data.vat_amount', 300)
        ->assertJsonPath('data.vat_rate', 15)
        ->assertJsonPath('data.total', 2300);

    $order = Order::sole();
    expect($order->vat_amount)->toEqual('300.00')
        ->and($order->vat_rate)->toEqual('15.00')
        ->and($order->total)->toEqual('2300.00');

    expect(Transaction::where('order_id', $order->id)->sole()->amount)->toEqual('2300.00');
});

it('computes VAT on the discounted subtotal when a coupon is applied', function () {
    Setting::set('vat_enabled', '1');
    Setting::set('vat_rate', '10');

    $coupon = Coupon::create([
        'code' => 'SAVE100',
        'type' => 'fixed',
        'value' => 100,
        'status' => 'active',
    ]);

    $product = Product::factory()->published()->create(['price' => 1000]);

    $response = $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'coupon_code' => $coupon->code,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    // subtotal 1000 − discount 100 = 900 taxable → 90 VAT → 990 total.
    $response->assertCreated()
        ->assertJsonPath('data.subtotal', 1000)
        ->assertJsonPath('data.discount', 100)
        ->assertJsonPath('data.vat_amount', 90)
        ->assertJsonPath('data.total', 990);
});

it('rounds VAT to two decimals', function () {
    Setting::set('vat_enabled', '1');
    Setting::set('vat_rate', '7.5');

    $product = Product::factory()->published()->create(['price' => 99.99]);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertCreated();

    $order = Order::sole();
    expect($order->vat_amount)->toEqual('7.50')
        ->and($order->total)->toEqual('107.49');
});
