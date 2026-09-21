<?php

namespace App\Livewire\Admin\Reports;

use App\Concerns\HasPerPage;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public ?int $viewingId = null;

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    public string $statusFilter = '';

    public string $paymentStatusFilter = '';

    public string $paymentMethodFilter = '';

    public string $fromDate = '';

    public string $toDate = '';

    public function mount(): void
    {
        $this->fromDate = $this->toDate = CarbonImmutable::now(display_timezone())->toDateString();
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
        $this->reset(['statusFilter', 'paymentStatusFilter', 'paymentMethodFilter']);
        $this->fromDate = $this->toDate = CarbonImmutable::now(display_timezone())->toDateString();
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
        $totals = (clone $this->filteredQuery())
            ->selectRaw('COUNT(*) AS total_orders')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total END), 0) AS total_revenue")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN total END), 0) AS pending_amount")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN 1 END), 0) AS pending_count")
            ->first();

        $revenueByMethod = (clone $this->filteredQuery())
            ->where('payment_status', 'paid')
            ->groupBy('payment_method')
            ->selectRaw('payment_method, COALESCE(SUM(total), 0) AS total')
            ->get()
            ->pluck('total', 'payment_method');

        $ordersByStatus = (clone $this->filteredQuery())
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) AS total')
            ->get()
            ->pluck('total', 'status');

        return view('livewire.admin.reports.index', [
            'orders' => $this->filteredQuery()->latest()->paginate($this->perPage),
            'totalOrders' => (int) $totals->total_orders,
            'totalRevenue' => (float) $totals->total_revenue,
            'pendingAmount' => (float) $totals->pending_amount,
            'pendingCount' => (int) $totals->pending_count,
            'revenueByMethod' => $revenueByMethod,
            'ordersByStatus' => $ordersByStatus,
        ])->layout('layouts.admin', ['title' => 'Reports']);
    }
}
