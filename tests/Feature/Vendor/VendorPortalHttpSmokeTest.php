<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVendor;
use App\Models\User;

it('renders the vendor order show page over real HTTP', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $vendor = ProductVendor::factory()->create();
    $vendor->users()->attach($user);
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

    $this->actingAs($user)
        ->get(route('vendor.orders.show', $order->id))
        ->assertOk()
        ->assertSee($order->order_number);
});
