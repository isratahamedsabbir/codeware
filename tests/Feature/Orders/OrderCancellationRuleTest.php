<?php

use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Livewire\Admin\Orders\Show as OrdersShow;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('defaults the cancellation cutoff to shipped', function () {
    expect(Setting::orderCancellationCutoffStatus())->toBe('shipped');
});

it('can be cancelled while pending or processing, but not once shipped or delivered', function () {
    expect(Order::factory()->status('pending')->create()->canBeCancelled())->toBeTrue()
        ->and(Order::factory()->status('processing')->create()->canBeCancelled())->toBeTrue()
        ->and(Order::factory()->status('shipped')->create()->canBeCancelled())->toBeFalse()
        ->and(Order::factory()->status('delivered')->create()->canBeCancelled())->toBeFalse();
});

it('an already-cancelled order is never cancellable again', function () {
    expect(Order::factory()->status('cancelled')->create()->canBeCancelled())->toBeFalse();
});

it('respects a custom cancellation cutoff', function () {
    Setting::set('order_cancellation_cutoff_status', 'processing');

    expect(Order::factory()->status('pending')->create()->canBeCancelled())->toBeTrue()
        ->and(Order::factory()->status('processing')->create()->canBeCancelled())->toBeFalse();
});

it('blocks setting an order to cancelled once it is past the cutoff', function () {
    $order = Order::factory()->status('shipped')->create();

    Livewire::test(OrdersShow::class, ['id' => $order->id])
        ->set('status', 'cancelled')
        ->call('updateStatus')
        ->assertHasErrors(['status']);

    expect($order->fresh()->status)->toBe('shipped');
});

it('allows setting an order to cancelled before the cutoff', function () {
    $order = Order::factory()->status('pending')->create();

    Livewire::test(OrdersShow::class, ['id' => $order->id])
        ->set('status', 'cancelled')
        ->call('updateStatus')
        ->assertHasNoErrors();

    expect($order->fresh()->status)->toBe('cancelled');
});

it('loads the current cancellation cutoff into the settings modal', function () {
    Setting::set('order_cancellation_cutoff_status', 'delivered');

    Livewire::test(OrdersIndex::class)
        ->assertSet('cancellationCutoffStatus', 'delivered');
});

it('saves a new cancellation cutoff from the settings modal', function () {
    Livewire::test(OrdersIndex::class)
        ->set('cancellationCutoffStatus', 'processing')
        ->call('saveCancellationRule');

    expect(Setting::orderCancellationCutoffStatus())->toBe('processing');
});

it('blocks staff from saving the cancellation rule even by calling the component method directly', function () {
    $staff = User::factory()->staff()->create();
    $this->actingAs($staff);

    Livewire::test(OrdersIndex::class)
        ->set('cancellationCutoffStatus', 'processing')
        ->call('saveCancellationRule')
        ->assertForbidden();

    expect(Setting::orderCancellationCutoffStatus())->toBe('shipped');
});
