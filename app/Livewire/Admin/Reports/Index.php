<?php

namespace App\Livewire\Admin\Reports;

use App\Models\Order;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public string $paymentStatusFilter = '';

    public string $paymentMethodFilter = '';

    public string $fromDate = '';

    public string $toDate = '';

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
        $this->reset(['statusFilter', 'paymentStatusFilter', 'paymentMethodFilter', 'fromDate', 'toDate']);
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    public function filters(): array
    {
        return array_filter([
            'status' => $this->statusFilter,
            'payment_status' => $this->paymentStatusFilter,
            'payment_method' => $this->paymentMethodFilter,
            'from' => $this->fromDate,
            'to' => $this->toDate,
        ]);
    }

    private function filteredQuery()
    {
        return Order::query()
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->paymentStatusFilter, fn ($q) => $q->where('payment_status', $this->paymentStatusFilter))
            ->when($this->paymentMethodFilter, fn ($q) => $q->where('payment_method', $this->paymentMethodFilter))
            ->when($this->fromDate, fn ($q) => $q->where('created_at', '>=', CarbonImmutable::parse($this->fromDate, display_timezone())->startOfDay()->utc()))
            ->when($this->toDate, fn ($q) => $q->where('created_at', '<=', CarbonImmutable::parse($this->toDate, display_timezone())->endOfDay()->utc()));
    }

    public function render()
    {
        $matching = $this->filteredQuery()->get();

        $revenueByMethod = $matching->where('payment_status', 'paid')
            ->groupBy('payment_method')
            ->map(fn (Collection $orders) => $orders->sum('total'));

        $ordersByStatus = $matching->groupBy('status')->map->count();

        return view('livewire.admin.reports.index', [
            'orders' => $this->filteredQuery()->latest()->paginate(Setting::perPage()),
            'totalOrders' => $matching->count(),
            'totalRevenue' => $matching->where('payment_status', 'paid')->sum('total'),
            'pendingAmount' => $matching->where('payment_status', 'pending')->sum('total'),
            'pendingCount' => $matching->where('payment_status', 'pending')->count(),
            'revenueByMethod' => $revenueByMethod,
            'ordersByStatus' => $ordersByStatus,
        ])->layout('layouts.admin', ['title' => 'Reports']);
    }
}
