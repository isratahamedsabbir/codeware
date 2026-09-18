<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
});

it('reports hasWarranty only when warranty_months is set and positive', function () {
    expect(Product::factory()->make(['warranty_months' => null])->hasWarranty())->toBeFalse()
        ->and(Product::factory()->make(['warranty_months' => 0])->hasWarranty())->toBeFalse()
        ->and(Product::factory()->make(['warranty_months' => 12])->hasWarranty())->toBeTrue();
});

it('shows the Warranty Card button only when the order has a warranty-eligible product', function () {
    // A full HTTP request rather than Livewire::test() — the button lives in
    // @push('page-header-actions'), which only flushes into the surrounding
    // layout on a real page render, not a component-only ->html().
    $this->actingAs($this->admin);

    $warrantedProduct = Product::factory()->create(['warranty_months' => 12]);
    $order = Order::factory()->create();
    OrderItem::factory()->for($order)->create(['product_id' => $warrantedProduct->id]);

    $this->get(route('admin.orders.show', $order->id))->assertSee('Warranty Card');

    $plainProduct = Product::factory()->create(['warranty_months' => null]);
    $orderWithoutWarranty = Order::factory()->create();
    OrderItem::factory()->for($orderWithoutWarranty)->create(['product_id' => $plainProduct->id]);

    $this->get(route('admin.orders.show', $orderWithoutWarranty->id))->assertDontSee('Warranty Card');
});

it('lets an admin download the warranty card for an eligible order', function () {
    $product = Product::factory()->create(['warranty_months' => 12]);
    $order = Order::factory()->create();
    OrderItem::factory()->for($order)->create(['product_id' => $product->id]);

    $response = $this->actingAs($this->admin)->get(route('admin.orders.warranty', $order));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('404s the admin warranty download for an order with nothing under warranty', function () {
    $product = Product::factory()->create(['warranty_months' => null]);
    $order = Order::factory()->create();
    OrderItem::factory()->for($order)->create(['product_id' => $product->id]);

    $this->actingAs($this->admin)->get(route('admin.orders.warranty', $order))->assertNotFound();
});

it('ignores a service line item even when a same-order product has no warranty', function () {
    $product = Product::factory()->create(['warranty_months' => null]);
    $service = Service::factory()->create();
    $order = Order::factory()->create();
    OrderItem::factory()->for($order)->create(['product_id' => $product->id]);
    OrderItem::factory()->forService()->for($order)->create(['service_id' => $service->id]);

    $this->actingAs($this->admin)->get(route('admin.orders.warranty', $order))->assertNotFound();
});

it('downloads the warranty card from the public API with a matching order number and email', function () {
    $product = Product::factory()->create(['warranty_months' => 12]);
    $order = Order::factory()->create(['customer_email' => 'jane@example.com']);
    OrderItem::factory()->for($order)->create(['product_id' => $product->id]);

    $response = $this->getJson("/api/v1/orders/{$order->order_number}/warranty?email=jane@example.com");

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('rejects the public warranty download with a mismatched email', function () {
    $product = Product::factory()->create(['warranty_months' => 12]);
    $order = Order::factory()->create(['customer_email' => 'jane@example.com']);
    OrderItem::factory()->for($order)->create(['product_id' => $product->id]);

    $this->getJson("/api/v1/orders/{$order->order_number}/warranty?email=someone-else@example.com")
        ->assertNotFound();
});
