<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

it('generates a PRD code for products on creation', function () {
    $product = Product::factory()->create();

    expect($product->code)->toStartWith('PRD-')
        ->and($product->code)->toHaveLength(12)
        ->and((string) $product->code)->toMatch('/^PRD-[A-Z0-9]{8}$/');
});

it('generates a USR code for users on creation', function () {
    $user = User::factory()->create();

    expect($user->code)->toStartWith('USR-')
        ->and($user->code)->toHaveLength(12)
        ->and((string) $user->code)->toMatch('/^USR-[A-Z0-9]{8}$/');
});

it('generates an ORD code for orders on creation', function () {
    $order = Order::factory()->create();

    expect($order->order_number)->toStartWith('ORD-')
        ->and($order->order_number)->toHaveLength(12)
        ->and((string) $order->order_number)->toMatch('/^ORD-[A-Z0-9]{8}$/');
});

it('generates a distinct code for every record', function () {
    $codes = collect()
        ->push(Product::factory()->create()->code)
        ->push(Product::factory()->create()->code)
        ->push(User::factory()->create()->code)
        ->push(User::factory()->create()->code)
        ->push(Order::factory()->create()->order_number)
        ->push(Order::factory()->create()->order_number);

    expect($codes->unique()->count())->toBe(6);
});

it('never overwrites an existing code', function () {
    $product = Product::factory()->create(['code' => 'PRD-CUSTOM123']);
    $user = User::factory()->create(['code' => 'USR-CUSTOM123']);
    $order = Order::factory()->create(['order_number' => 'ORD-CUSTOM123']);

    expect($product->code)->toBe('PRD-CUSTOM123')
        ->and($user->code)->toBe('USR-CUSTOM123')
        ->and($order->order_number)->toBe('ORD-CUSTOM123');
});
