<?php

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;

/**
 * Builds a valid order payload (one product at 500 x 2 = 1000) so each test can
 * sprinkle in overrides like a coupon_code.
 */
function orderPayload(array $overrides = []): array
{
    $product = Product::factory()->published()->create(['price' => 500]);

    return array_merge([
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ], $overrides);
}

it('applies a percentage coupon and persists it on the order', function () {
    $coupon = Coupon::factory()->active()->create([
        'code' => 'SAVE10',
        'type' => 'percentage',
        'value' => 10,
        'min_order_amount' => null,
        'max_uses' => null,
    ]);

    $response = $this->postJson('/api/v1/orders', orderPayload(['coupon_code' => 'save10']));

    $response->assertCreated();
    $response->assertJsonPath('data.subtotal', 1000);
    $response->assertJsonPath('data.discount', 100);
    $response->assertJsonPath('data.total', 900);
    $response->assertJsonPath('data.coupon_code', 'SAVE10');

    $order = Order::sole();
    expect($order->coupon_code)->toBe('SAVE10')
        ->and((float) $order->discount)->toBe(100.0)
        ->and((float) $order->total)->toBe(900.0)
        ->and($coupon->fresh()->used_count)->toBe(1);
});

it('applies a fixed coupon but never discounts below zero', function () {
    $coupon = Coupon::factory()->active()->create([
        'code' => 'FIXED500',
        'type' => 'fixed',
        'value' => 500,
        'min_order_amount' => null,
        'max_uses' => null,
    ]);

    $this->postJson('/api/v1/orders', orderPayload(['coupon_code' => 'FIXED500']))
        ->assertCreated()
        ->assertJsonPath('data.discount', 500)
        ->assertJsonPath('data.total', 500);

    $big = Coupon::factory()->active()->create([
        'code' => 'BIGFIXED',
        'type' => 'fixed',
        'value' => 5000,
        'min_order_amount' => null,
        'max_uses' => null,
    ]);

    $this->postJson('/api/v1/orders', orderPayload(['coupon_code' => 'BIGFIXED']))
        ->assertCreated()
        ->assertJsonPath('data.discount', 1000)
        ->assertJsonPath('data.total', 0);

    expect($coupon->fresh()->used_count)->toBe(1)
        ->and($big->fresh()->used_count)->toBe(1);
});

it('rejects a coupon when any product in the cart already has a discount price', function () {
    $discounted = Product::factory()->published()->create(['price' => 500, 'discount_price' => 400]);

    $coupon = Coupon::factory()->active()->create([
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
        'coupon_code' => 'SAVE10',
        'items' => [
            ['product_id' => $discounted->id, 'quantity' => 1],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['coupon_code']);

    expect(Order::count())->toBe(0)
        ->and($coupon->fresh()->used_count)->toBe(0);
});

it('rejects unknown and unserviceable coupons without creating an order', function () {
    $this->postJson('/api/v1/orders', orderPayload(['coupon_code' => 'NOEXIST']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['coupon_code']);

    $expired = Coupon::factory()->active()->expired()->create(['code' => 'OLDMATE']);

    $this->postJson('/api/v1/orders', orderPayload(['coupon_code' => 'OLDMATE']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['coupon_code']);

    $inactive = Coupon::factory()->inactive()->create(['code' => 'OFFCODE']);

    $this->postJson('/api/v1/orders', orderPayload(['coupon_code' => 'OFFCODE']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['coupon_code']);

    $exhausted = Coupon::factory()->active()->create(['code' => 'USEDUP', 'max_uses' => 1, 'used_count' => 1]);

    $this->postJson('/api/v1/orders', orderPayload(['coupon_code' => 'USEDUP']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['coupon_code']);

    expect(Order::count())->toBe(0)
        ->and($expired->fresh()->used_count)->toBe(0)
        ->and($inactive->fresh()->used_count)->toBe(0)
        ->and($exhausted->fresh()->used_count)->toBe(1);
});

it('rejects a coupon when the order is below the minimum order amount', function () {
    $coupon = Coupon::factory()->active()->create([
        'code' => 'MIN2000',
        'type' => 'percentage',
        'value' => 10,
        'min_order_amount' => 2000,
        'max_uses' => null,
    ]);

    $this->postJson('/api/v1/orders', orderPayload(['coupon_code' => 'MIN2000']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['coupon_code']);

    expect(Order::count())->toBe(0)
        ->and($coupon->fresh()->used_count)->toBe(0);
});

it('rejects a coupon restricted to other products', function () {
    $other = Product::factory()->published()->create();
    $inCart = Product::factory()->published()->create(['price' => 500]);

    $coupon = Coupon::factory()->active()->create([
        'code' => 'ONLYONE',
        'type' => 'percentage',
        'value' => 10,
        'min_order_amount' => null,
        'max_uses' => null,
    ]);
    $coupon->products()->attach($other->id);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'coupon_code' => 'ONLYONE',
        'items' => [
            ['product_id' => $inCart->id, 'quantity' => 1],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['coupon_code']);

    expect(Order::count())->toBe(0)
        ->and($coupon->fresh()->used_count)->toBe(0);
});

it('accepts a coupon restricted to a product that is in the cart', function () {
    $product = Product::factory()->published()->create(['price' => 500]);

    $coupon = Coupon::factory()->active()->create([
        'code' => 'ONLYONE',
        'type' => 'percentage',
        'value' => 10,
        'min_order_amount' => null,
        'max_uses' => null,
    ]);
    $coupon->products()->attach($product->id);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'coupon_code' => 'ONLYONE',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 2],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.total', 900);

    expect(Order::sole()->total)->toEqual('900.00')
        ->and($coupon->fresh()->used_count)->toBe(1);
});

it('has no coupon fields on a plain order', function () {
    $this->postJson('/api/v1/orders', orderPayload())
        ->assertCreated()
        ->assertJsonPath('data.coupon_code', null)
        ->assertJsonPath('data.discount', 0)
        ->assertJsonPath('data.total', 1000);

    expect(Order::sole()->coupon_code)->toBeNull()
        ->and((float) Order::sole()->discount)->toBe(0.0);
});
