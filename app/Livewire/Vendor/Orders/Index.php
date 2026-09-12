<?php

namespace App\Livewire\Vendor\Orders;

use App\Concerns\HasPerPage;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $vendorIds = Auth::user()->vendors()->pluck('product_vendors.id');

        // Only ever loads each order's items belonging to these vendors — a
        // vendor never sees another vendor's line items within a shared order,
        // matching Show::mount()'s same scoping.
        $orders = Order::whereHas('items.product', fn ($q) => $q->whereIn('vendor_id', $vendorIds))
            ->when($this->search, fn ($q) => $q->where('order_number', 'like', "%{$this->search}%"))
            ->with(['items' => fn ($q) => $q->whereHas('product', fn ($q2) => $q2->whereIn('vendor_id', $vendorIds))])
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.vendor.orders.index', [
            'orders' => $orders,
        ])->layout('layouts.vendor', ['title' => 'Orders']);
    }
}
