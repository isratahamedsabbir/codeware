<?php

use App\Livewire\Admin\Products\Show as ProductShow;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('shows the product\'s own details', function () {
    $product = Product::factory()->create([
        'name' => ['en' => 'Wireless Mouse', 'bn' => ''],
        'sku' => 'MOUSE-1',
    ]);

    Livewire::test(ProductShow::class, ['id' => $product->id])
        ->assertOk()
        ->assertSee('Wireless Mouse')
        ->assertSee('MOUSE-1');
});

it('only lists orders that include this product', function () {
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();

    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

    $otherOrder = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $otherOrder->id, 'product_id' => $otherProduct->id]);

    Livewire::test(ProductShow::class, ['id' => $product->id])
        ->assertSee($order->order_number)
        ->assertDontSee($otherOrder->order_number);
});

it('shows only this product\'s own line item quantity/total from a mixed order', function () {
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();

    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id, 'product_id' => $product->id,
        'quantity' => 3, 'unit_price' => 100, 'line_total' => 300,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id, 'product_id' => $otherProduct->id,
        'quantity' => 99, 'unit_price' => 5, 'line_total' => 495,
    ]);

    Livewire::test(ProductShow::class, ['id' => $product->id])
        ->assertSee('300.00')
        ->assertDontSee('495.00');
});

it('filters a product\'s orders by order number', function () {
    $product = Product::factory()->create();

    $orderA = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $orderA->id, 'product_id' => $product->id]);

    $orderB = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $orderB->id, 'product_id' => $product->id]);

    Livewire::test(ProductShow::class, ['id' => $product->id])
        ->set('search', $orderA->order_number)
        ->assertSee($orderA->order_number)
        ->assertDontSee($orderB->order_number);
});

it('can show a soft-deleted product', function () {
    $product = Product::factory()->create(['name' => ['en' => 'Deleted Item', 'bn' => '']]);
    $product->delete();

    Livewire::test(ProductShow::class, ['id' => $product->id])
        ->assertOk()
        ->assertSee('Deleted Item')
        ->assertSee('Deleted');
});

it('links from the products index view action to the product show page', function () {
    $product = Product::factory()->create();

    $this->get(route('admin.products.show', $product->id))->assertOk();
});
