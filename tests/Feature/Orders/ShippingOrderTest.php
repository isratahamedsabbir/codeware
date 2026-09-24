<?php

use App\Livewire\Frontend\Checkout;
use App\Models\Coupon;
use App\Models\Language;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Cart;

beforeEach(function () {
    Setting::set('site_theme', 'ecommerce');
    Setting::set('vat_enabled', '0');
    Setting::set('vat_rate', '0');

    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true]);

    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'sort_order' => 0]);
});

it('lists the active shipping methods on the checkout page', function () {
    ShippingMethod::create(['name' => 'Inside Dhaka (Standard)', 'cost' => 60, 'status' => 'active']);
    ShippingMethod::create(['name' => 'Store Pickup', 'cost' => 0, 'status' => 'active']);
    ShippingMethod::create(['name' => 'Retired Method', 'cost' => 9, 'status' => 'inactive']);

    $product = Product::factory()->published()->create(['name' => ['en' => 'Ship Me', 'bn' => ''], 'sort_order' => 0, 'quantity' => 10, 'price' => 100]);
    pairPageFor($product, 'product', 'ship-me', User::factory()->create()->id);
    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->assertSee('Inside Dhaka (Standard)')
        ->assertSee('Store Pickup')
        ->assertDontSee('Retired Method');
});

it('does not apply shipping when no shipping methods are configured', function () {
    $product = Product::factory()->published()->create(['name' => ['en' => 'No Ship', 'bn' => ''], 'sort_order' => 0, 'quantity' => 10, 'price' => 400]);
    pairPageFor($product, 'product', 'no-ship', User::factory()->create()->id);
    Cart::add($product->id, 2);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Jane Doe')
        ->set('customer_email', 'jane@example.com')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::sole();
    expect($order->shipping_method)->toBeNull()
        ->and($order->shipping_cost)->toEqual('0.00')
        ->and($order->total)->toEqual('800.00');
});

it('applies the selected shipping method cost to the theme checkout total', function () {
    ShippingMethod::create(['name' => 'Inside Dhaka (Standard)', 'cost' => 60, 'status' => 'active']);

    $product = Product::factory()->published()->create(['name' => ['en' => 'Ship Total', 'bn' => ''], 'sort_order' => 0, 'quantity' => 10, 'price' => 400]);
    pairPageFor($product, 'product', 'ship-total', User::factory()->create()->id);
    Cart::add($product->id, 2);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Jane Doe')
        ->set('customer_email', 'jane@example.com')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->set('payment_method', 'cod')
        ->call('placeOrder')
        ->assertRedirect(route('checkout.confirmation', Order::sole()->order_number));

    $order = Order::sole();
    expect($order->shipping_method)->toBe('Inside Dhaka (Standard)')
        ->and($order->shipping_cost)->toEqual('60.00')
        ->and($order->subtotal)->toEqual('800.00')
        ->and($order->total)->toEqual('860.00');

    expect(Transaction::where('order_id', $order->id)->sole()->amount)->toEqual('860.00');
});

it('reflects the estimated shipping cost in the live checkout summary', function () {
    ShippingMethod::create(['name' => 'Express Delivery', 'cost' => 250, 'status' => 'active']);

    $product = Product::factory()->published()->create(['name' => ['en' => 'Ship Live', 'bn' => ''], 'sort_order' => 0, 'quantity' => 10, 'price' => 100]);
    pairPageFor($product, 'product', 'ship-live', User::factory()->create()->id);
    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->assertSet('shippingLabel', 'Express Delivery')
        ->assertSet('shipping', 250.0)
        ->assertSet('total', 350.0)
        ->assertSee('Shipping');
});

it('re-prices the checkout summary when the shopper switches shipping method', function () {
    ShippingMethod::create(['name' => 'Pickup', 'cost' => 0, 'status' => 'active']);
    $express = ShippingMethod::create(['name' => 'Express', 'cost' => 120, 'status' => 'active']);

    $product = Product::factory()->published()->create(['name' => ['en' => 'Ship Switch', 'bn' => ''], 'sort_order' => 0, 'quantity' => 10, 'price' => 100]);
    pairPageFor($product, 'product', 'ship-switch', User::factory()->create()->id);
    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->assertSet('shippingLabel', 'Pickup')
        ->assertSet('total', 100.0)
        ->set('shipping_method_id', $express->id)
        ->assertSet('shippingLabel', 'Express')
        ->assertSet('shipping', 120.0)
        ->assertSet('total', 220.0);
});

it('rejects a shipping method that is no longer active on the theme checkout', function () {
    $retired = ShippingMethod::create(['name' => 'Retired', 'cost' => 60, 'status' => 'inactive']);

    $product = Product::factory()->published()->create(['name' => ['en' => 'Retired Ship', 'bn' => ''], 'sort_order' => 0, 'quantity' => 10, 'price' => 400]);
    pairPageFor($product, 'product', 'retired-ship', User::factory()->create()->id);
    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Jane Doe')
        ->set('customer_email', 'jane@example.com')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->set('shipping_method_id', $retired->id)
        ->call('placeOrder')
        ->assertHasErrors('shipping_method_id');

    expect(Order::count())->toBe(0);
});

it('adds shipping cost to a product order through the order API', function () {
    $method = ShippingMethod::create(['name' => 'Outside Dhaka (Standard)', 'cost' => 120, 'status' => 'active']);
    $product = Product::factory()->published()->create(['price' => 500]);

    $response = $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'shipping_method_id' => $method->id,
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.shipping_method', 'Outside Dhaka (Standard)')
        ->assertJsonPath('data.shipping_cost', 120)
        ->assertJsonPath('data.total', 620);

    $order = Order::sole();
    expect($order->shipping_cost)->toEqual('120.00')
        ->and($order->total)->toEqual('620.00');

    expect(Transaction::where('order_id', $order->id)->sole()->amount)->toEqual('620.00');
});

it('shipping is optional on the order API and defaults to zero', function () {
    $product = Product::factory()->published()->create(['price' => 500]);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.shipping_method', null)
        ->assertJsonPath('data.shipping_cost', 0)
        ->assertJsonPath('data.total', 500);
});

it('rejects an inactive or unknown shipping method on the order API', function () {
    $inactive = ShippingMethod::create(['name' => 'Retired', 'cost' => 60, 'status' => 'inactive']);
    $product = Product::factory()->published()->create(['price' => 500]);

    $payload = [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'payment_method' => 'cod',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ];

    $this->postJson('/api/v1/orders', $payload + ['shipping_method_id' => $inactive->id])
        ->assertJsonValidationErrors(['shipping_method_id']);

    $this->postJson('/api/v1/orders', $payload + ['shipping_method_id' => 99999])
        ->assertJsonValidationErrors(['shipping_method_id']);

    expect(Order::count())->toBe(0);
});

it('never charges shipping on a service-only order, even when an id is sent', function () {
    $method = ShippingMethod::create(['name' => 'Express Delivery', 'cost' => 250, 'status' => 'active']);
    $service = Service::factory()->published()->create(['price' => 800]);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_method_id' => $method->id,
        'payment_method' => 'cod',
        'items' => [
            ['service_id' => $service->id, 'quantity' => 1],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.shipping_method', null)
        ->assertJsonPath('data.shipping_cost', 0)
        ->assertJsonPath('data.total', 800);
});

it('computes shipping on top of the discounted subtotal with a coupon on the API', function () {
    $method = ShippingMethod::create(['name' => 'Inside Dhaka (Standard)', 'cost' => 60, 'status' => 'active']);
    $coupon = Coupon::create(['code' => 'SHIP50', 'type' => 'fixed', 'value' => 50, 'status' => 'active']);
    $product = Product::factory()->published()->create(['price' => 1000]);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'shipping_method_id' => $method->id,
        'payment_method' => 'cod',
        'coupon_code' => $coupon->code,
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.subtotal', 1000)
        ->assertJsonPath('data.discount', 50)
        ->assertJsonPath('data.shipping_cost', 60)
        // 1000 − 50 discount + 60 shipping = 1010.
        ->assertJsonPath('data.total', 1010);
});

it('shows the VAT line alongside shipping in the checkout summary when VAT is on', function () {
    Setting::set('vat_enabled', '1');
    Setting::set('vat_rate', '15');
    Setting::set('vat_label', 'VAT');

    ShippingMethod::create(['name' => 'Express', 'cost' => 60, 'status' => 'active']);

    $product = Product::factory()->published()->create(['name' => ['en' => 'Vat Ship', 'bn' => ''], 'sort_order' => 0, 'quantity' => 10, 'price' => 200]);
    pairPageFor($product, 'product', 'vat-ship', User::factory()->create()->id);
    Cart::add($product->id, 1);

    // 200 subtotal + 30 VAT (15%) + 60 shipping = 290.
    Livewire::test(Checkout::class)
        ->assertSet('vat', 30.0)
        ->assertSet('total', 290.0)
        ->assertSeeInOrder(['VAT', format_money(30), 'Shipping', format_money(60)]);
});

it('includes shipping with VAT on the total when both apply', function () {
    Setting::set('vat_enabled', '1');
    Setting::set('vat_rate', '10');

    $method = ShippingMethod::create(['name' => 'Express Delivery', 'cost' => 100, 'status' => 'active']);
    $product = Product::factory()->published()->create(['price' => 1000]);

    $this->postJson('/api/v1/orders', [
        'customer_name' => 'Jane Doe',
        'customer_email' => 'jane@example.com',
        'customer_phone' => '01712345678',
        'shipping_address' => '123 Main St, Dhaka',
        'shipping_method_id' => $method->id,
        'payment_method' => 'cod',
        'items' => [
            ['product_id' => $product->id, 'quantity' => 1],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.vat_amount', 100)
        ->assertJsonPath('data.shipping_cost', 100)
        // 1000 + 100 VAT + 100 shipping = 1200.
        ->assertJsonPath('data.total', 1200);
});

it('renders the shipping line on the order confirmation page', function () {
    ShippingMethod::create(['name' => 'Inside Dhaka (Standard)', 'cost' => 60, 'status' => 'active']);

    $product = Product::factory()->published()->create(['name' => ['en' => 'Confirm Ship', 'bn' => ''], 'sort_order' => 0, 'quantity' => 10, 'price' => 200]);
    pairPageFor($product, 'product', 'confirm-ship', User::factory()->create()->id);
    Cart::add($product->id, 1);

    Livewire::test(Checkout::class)
        ->set('customer_name', 'Jane Doe')
        ->set('customer_email', 'jane@example.com')
        ->set('customer_phone', '01712345678')
        ->set('shipping_address', '123 Main St, Dhaka')
        ->call('placeOrder');

    $order = Order::sole();

    $this->get(route('checkout.confirmation', $order->order_number))
        ->assertOk()
        ->assertSee('Shipping')
        ->assertSee('Inside Dhaka (Standard)')
        // format_money strips the trailing .00, so 260 renders as "৳ 260".
        ->assertSee('৳ 260');
});
