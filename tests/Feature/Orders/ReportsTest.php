<?php

use App\Livewire\Admin\Reports\Index as ReportsIndex;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('summarizes total orders and revenue from paid orders only', function () {
    Order::factory()->paymentStatus('paid')->create(['total' => 1000]);
    Order::factory()->paymentStatus('paid')->create(['total' => 500]);
    Order::factory()->paymentStatus('pending')->create(['total' => 300]);

    Livewire::test(ReportsIndex::class)
        ->assertSee('1,500.00') // revenue from paid orders
        ->assertSee('300.00'); // pending amount
});

it('filters the report by date range', function () {
    Order::factory()->create(['customer_name' => 'Old Order', 'created_at' => now()->subDays(30)]);
    Order::factory()->create(['customer_name' => 'Recent Order', 'created_at' => now()]);

    Livewire::test(ReportsIndex::class)
        ->set('fromDate', now()->subDays(2)->toDateString())
        ->assertSee('Recent Order')
        ->assertDontSee('Old Order');
});

it('exports the report as csv honoring active filters', function () {
    Order::factory()->status('delivered')->create(['customer_name' => 'Delivered Export']);
    Order::factory()->status('pending')->create(['customer_name' => 'Pending Export']);

    $csv = $this->get(route('admin.reports.export', ['status' => 'delivered']))->streamedContent();

    expect($csv)->toContain('Delivered Export')
        ->and($csv)->not->toContain('Pending Export');
});
