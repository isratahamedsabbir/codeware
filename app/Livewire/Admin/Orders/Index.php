<?php

namespace App\Livewire\Admin\Orders;

use App\Concerns\HasPerPage;
use App\Models\Order;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $paymentStatusFilter = '';

    public string $paymentMethodFilter = '';

    public string $fromDate = '';

    public string $toDate = '';

    public string $productFilter = '';

    public string $productSearch = '';

    public string $priceMin = '';

    public string $priceMax = '';

    public function mount(): void
    {
        $this->fromDate = $this->toDate = CarbonImmutable::now(display_timezone())->toDateString();
    }

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

    public function updatedProductFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMin(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMax(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'paymentStatusFilter', 'paymentMethodFilter', 'productFilter', 'productSearch', 'priceMin', 'priceMax']);
        $this->fromDate = $this->toDate = CarbonImmutable::now(display_timezone())->toDateString();
        $this->resetPage();
    }

    /**
     * @return Collection<int, object{id: int, label: string}>
     */
    public function productOptions(): Collection
    {
        return Product::query()
            ->when($this->productSearch, fn ($q) => $q
                ->where('name->en', 'like', "%{$this->productSearch}%")
                ->orWhere('name->bn', 'like', "%{$this->productSearch}%"))
            ->orderBy('name->en')
            ->limit(30)
            ->get(['id', 'name'])
            ->map(fn (Product $product) => (object) [
                'id' => $product->id,
                'label' => $product->getTranslation('name', 'en', false),
            ]);
    }

    public function selectedProductLabel(): ?string
    {
        if (! $this->productFilter) {
            return null;
        }

        return Product::query()->find($this->productFilter)?->getTranslation('name', 'en', false);
    }

    /**
     * @return array<string, string>
     */
    public function filters(): array
    {
        return array_filter([
            'search' => $this->search,
            'status' => $this->statusFilter,
            'payment_status' => $this->paymentStatusFilter,
            'payment_method' => $this->paymentMethodFilter,
            'from' => $this->fromDate,
            'to' => $this->toDate,
            'product' => $this->productFilter,
            'price_min' => $this->priceMin,
            'price_max' => $this->priceMax,
        ]);
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
            ->when($this->toDate, fn ($q) => $q->where('created_at', '<=', CarbonImmutable::parse($this->toDate, display_timezone())->endOfDay()->utc()))
            ->when($this->productFilter, fn ($q) => $q->whereHas('items', fn ($q2) => $q2->where('product_id', $this->productFilter)))
            ->when($this->priceMin !== '', fn ($q) => $q->where('total', '>=', $this->priceMin))
            ->when($this->priceMax !== '', fn ($q) => $q->where('total', '<=', $this->priceMax));
    }

    public function render()
    {
        return view('livewire.admin.orders.index', [
            'orders' => $this->filteredQuery()
                ->withCount('items')
                ->latest()
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Orders']);
    }
}
