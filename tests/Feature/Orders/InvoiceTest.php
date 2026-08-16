<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\URL;

it('lets an admin view the invoice page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $order = Order::factory()->has(OrderItem::factory()->count(2), 'items')->create();

    $this->actingAs($admin)
        ->get(route('admin.orders.invoice', $order))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Download PDF', false);
});

it('lets an admin download the invoice as a pdf', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $order = Order::factory()->has(OrderItem::factory()->count(2), 'items')->create();

    $response = $this->actingAs($admin)->get(route('admin.orders.invoice.download', $order));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('blocks staff from admin invoice routes', function () {
    $this->seed(RolePermissionSeeder::class);
    $staff = User::factory()->create(['is_admin' => false]);
    $staff->assignRole('staff');
    $order = Order::factory()->create();

    $this->actingAs($staff)
        ->get(route('admin.orders.invoice', $order))
        ->assertForbidden();
});

it('serves the public invoice page for a validly signed url', function () {
    $order = Order::factory()->has(OrderItem::factory()->count(2), 'items')->create();

    $url = URL::signedRoute('invoices.public.show', ['order' => $order->order_number]);

    $this->get($url)
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('data:image/png;base64,', false);
});

it('rejects the public invoice page without a valid signature', function () {
    $order = Order::factory()->create();

    $this->get('/invoices/'.$order->order_number)->assertForbidden();
    $this->get('/invoices/'.$order->order_number.'?signature=tampered')->assertForbidden();
});

it('serves the public invoice pdf download for a validly signed url', function () {
    $order = Order::factory()->has(OrderItem::factory()->count(2), 'items')->create();

    $url = URL::signedRoute('invoices.public.download', ['order' => $order->order_number]);

    $response = $this->get($url);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});
