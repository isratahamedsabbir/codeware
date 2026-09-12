<?php

use App\Livewire\Vendor\Dashboard;
use App\Livewire\Vendor\Orders\Index as OrdersIndex;
use App\Livewire\Vendor\Orders\Show as OrdersShow;
use App\Livewire\Vendor\Products\Index as ProductsIndex;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['is_admin' => false]);
    $this->vendorA = ProductVendor::factory()->create(['name' => 'Vendor A']);
    $this->vendorB = ProductVendor::factory()->create(['name' => 'Vendor B']);
    $this->vendorA->users()->attach($this->user);
    $this->actingAs($this->user);
});

it('only lists products belonging to the assigned vendor', function () {
    $mine = Product::factory()->create(['vendor_id' => $this->vendorA->id, 'name' => ['en' => 'My Widget', 'bn' => '']]);
    Product::factory()->create(['vendor_id' => $this->vendorB->id, 'name' => ['en' => 'Their Gadget', 'bn' => '']]);
    Product::factory()->create(['vendor_id' => null, 'name' => ['en' => 'Unassigned Thing', 'bn' => '']]);

    Livewire::test(ProductsIndex::class)
        ->assertSee('My Widget')
        ->assertDontSee('Their Gadget')
        ->assertDontSee('Unassigned Thing');
});

it('lists products across every vendor a user is assigned to', function () {
    $this->vendorB->users()->attach($this->user);
    Product::factory()->create(['vendor_id' => $this->vendorA->id, 'name' => ['en' => 'From A', 'bn' => '']]);
    Product::factory()->create(['vendor_id' => $this->vendorB->id, 'name' => ['en' => 'From B', 'bn' => '']]);

    Livewire::test(ProductsIndex::class)
        ->assertSee('From A')
        ->assertSee('From B');
});

it('only lists orders containing an item from the assigned vendor\'s products', function () {
    $myProduct = Product::factory()->create(['vendor_id' => $this->vendorA->id]);
    $theirProduct = Product::factory()->create(['vendor_id' => $this->vendorB->id]);

    $myOrder = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $myOrder->id, 'product_id' => $myProduct->id]);

    $theirOrder = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $theirOrder->id, 'product_id' => $theirProduct->id]);

    Livewire::test(OrdersIndex::class)
        ->assertSee($myOrder->order_number)
        ->assertDontSee($theirOrder->order_number);
});

it('shows only the assigned vendor\'s line items within a shared order, not the whole order', function () {
    $myProduct = Product::factory()->create(['vendor_id' => $this->vendorA->id]);
    $theirProduct = Product::factory()->create(['vendor_id' => $this->vendorB->id]);

    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $myProduct->id, 'product_name' => 'My Line Item']);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $theirProduct->id, 'product_name' => 'Their Line Item']);

    Livewire::test(OrdersShow::class, ['orderId' => $order->id])
        ->assertSee('My Line Item')
        ->assertDontSee('Their Line Item');
});

it('404s an order that has no items from the assigned vendor at all', function () {
    $theirProduct = Product::factory()->create(['vendor_id' => $this->vendorB->id]);
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $theirProduct->id]);

    Livewire::test(OrdersShow::class, ['orderId' => $order->id])
        ->assertStatus(404);
});

it('reports correct product and order counts on the dashboard', function () {
    $myProduct = Product::factory()->create(['vendor_id' => $this->vendorA->id]);
    Product::factory()->create(['vendor_id' => $this->vendorB->id]);

    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $myProduct->id]);

    Livewire::test(Dashboard::class)
        ->assertViewHas('productsCount', 1)
        ->assertViewHas('ordersCount', 1)
        ->assertSee('Vendor A');
});
