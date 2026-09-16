<?php

use App\Livewire\Admin\Orders\Index as OrdersIndex;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('keeps the correct breadcrumb after a wire:click round trip', function () {
    $order = Order::factory()->create();

    Livewire::test(OrdersIndex::class)
        ->call('toggleSelect', $order->id)
        ->assertSee('Orders')
        ->assertDontSee('Update');
});

it('hides the row checkboxes until a bulk selection is active', function () {
    $orders = Order::factory()->count(2)->create();

    $component = Livewire::test(OrdersIndex::class);

    $component->assertDontSeeHtml('type="checkbox"');

    $component->call('toggleSelect', $orders[0]->id)
        ->assertSeeHtml('type="checkbox"');
});

it('shows only the export button in the bulk toolbar, never a delete button', function () {
    $order = Order::factory()->create();

    Livewire::test(OrdersIndex::class)
        ->assertDontSee('Export (')
        ->call('toggleSelect', $order->id)
        ->assertSee('Export (1)')
        ->assertDontSee('Delete (');
});

it('toggles an order id in and out of the selection', function () {
    $order = Order::factory()->create();

    Livewire::test(OrdersIndex::class)
        ->call('toggleSelect', $order->id)
        ->assertSet('selectedIds', [$order->id])
        ->call('toggleSelect', $order->id)
        ->assertSet('selectedIds', []);
});

it('has no bulkDelete method — Orders is deliberately read-only for bulk actions', function () {
    expect(method_exists(OrdersIndex::class, 'bulkDelete'))->toBeFalse();
    expect(method_exists(OrdersIndex::class, 'confirmBulkDelete'))->toBeFalse();
});

it('exports only the requested order ids as a downloadable csv, ignoring filters', function () {
    $included = Order::factory()->create(['customer_name' => 'Included Customer']);
    $excluded = Order::factory()->create(['customer_name' => 'Excluded Customer']);

    $response = $this->get(route('admin.orders.export', ['ids' => [$included->id]]));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Included Customer')
        ->and($csv)->not->toContain('Excluded Customer');
});

it('falls back to the active filters when no ids are given', function () {
    Order::factory()->status('delivered')->create(['customer_name' => 'Delivered Customer']);
    Order::factory()->status('pending')->create(['customer_name' => 'Pending Customer']);

    $response = $this->get(route('admin.orders.export', ['status' => 'delivered']));

    $csv = $response->streamedContent();

    expect($csv)->toContain('Delivered Customer')
        ->and($csv)->not->toContain('Pending Customer');
});
