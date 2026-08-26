<?php

namespace App\Livewire\Admin\Orders;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $paymentStatusFilter = '';

    public string $paymentMethodFilter = '';

    public string $fromDate = '';

    public string $toDate = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentMethodFilter(): void
    {
        $this->resetPage();
    }

    public function updatedFromDate(): void
    {
        $this->resetPage();
    }

    public function updatedToDate(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'paymentStatusFilter', 'paymentMethodFilter', 'fromDate', 'toDate']);
        $this->resetPage();
    }

    private function filteredQuery()
    {
        return Order::query()
            ->when($this->search, fn ($q) => $q
                ->where('order_number', 'like', "%{$this->search}%")
                ->orWhere('customer_name', 'like', "%{$this->search}%")
                ->orWhere('customer_email', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->paymentStatusFilter, fn ($q) => $q->where('payment_status', $this->paymentStatusFilter))
            ->when($this->paymentMethodFilter, fn ($q) => $q->where('payment_method', $this->paymentMethodFilter))
            ->when($this->fromDate, fn ($q) => $q->where('created_at', '>=', CarbonImmutable::parse($this->fromDate, display_timezone())->startOfDay()->utc()))
            ->when($this->toDate, fn ($q) => $q->where('created_at', '<=', CarbonImmutable::parse($this->toDate, display_timezone())->endOfDay()->utc()));
    }

    public function render()
    {
        return view('livewire.admin.orders.index', [
            'orders' => $this->filteredQuery()
                ->withCount('items')
                ->latest()
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => 'Orders']);
    }
}
