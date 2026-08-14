<?php

use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrdersShow;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('renders the orders list', function () {
    Order::factory()->create(['customer_name' => 'Alice Example']);

    Livewire::test(OrdersIndex::class)->assertSee('Alice Example');
});

it('filters orders by status', function () {
    Order::factory()->status('delivered')->create(['customer_name' => 'Delivered Customer']);
    Order::factory()->status('pending')->create(['customer_name' => 'Pending Customer']);

    Livewire::test(OrdersIndex::class)
        ->set('statusFilter', 'delivered')
        ->assertSee('Delivered Customer')
        ->assertDontSee('Pending Customer');
});

it('filters orders by payment method', function () {
    Order::factory()->paymentMethod('cod')->create(['customer_name' => 'Cod Customer']);
    Order::factory()->paymentMethod('stripe')->create(['customer_name' => 'Stripe Customer']);

    Livewire::test(OrdersIndex::class)
        ->set('paymentMethodFilter', 'stripe')
        ->assertSee('Stripe Customer')
        ->assertDontSee('Cod Customer');
});

it('searches orders by order number or customer', function () {
    $order = Order::factory()->create(['customer_name' => 'Findable Customer']);
    Order::factory()->create(['customer_name' => 'Someone Else']);

    Livewire::test(OrdersIndex::class)
        ->set('search', $order->order_number)
        ->assertSee('Findable Customer')
        ->assertDontSee('Someone Else');
});

it('shows order details with items and transactions', function () {
    $order = Order::factory()->has(OrderItem::factory()->count(2), 'items')->create();

    Livewire::test(OrdersShow::class, ['id' => $order->id])
        ->assertSee($order->order_number)
        ->assertSee($order->customer_name);
});

it('updates order status and payment status, and marks the transaction paid', function () {
    $order = Order::factory()->create(['status' => 'pending', 'payment_status' => 'pending']);
    Transaction::factory()->for($order)->create(['status' => 'pending']);

    Livewire::test(OrdersShow::class, ['id' => $order->id])
        ->set('status', 'delivered')
        ->set('paymentStatus', 'paid')
        ->call('updateStatus');

    $order->refresh();
    expect($order->status)->toBe('delivered')
        ->and($order->payment_status)->toBe('paid');

    expect($order->transactions()->latest()->first()->status)->toBe('success');
});

it('blocks staff from the orders and reports screens', function () {
    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create(['is_admin' => false]);
    $staff->assignRole('staff');

    $this->actingAs($staff)->get(route('admin.orders'))->assertForbidden();
    $this->actingAs($staff)->get(route('admin.reports'))->assertForbidden();
});

it('is blocked when the orders feature is disabled', function () {
    Setting::set('feature_orders', false);

    $this->get(route('admin.orders'))->assertNotFound();
});
