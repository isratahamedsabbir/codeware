<?php

namespace App\Livewire\Delivery\Orders;

use App\Concerns\HasPerPage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    /** 'pending' (still to deliver) | 'delivered' | '' (all). */
    #[Url]
    public string $filter = 'pending';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        // Only ever the orders assigned to this rider (Admin → Orders → Show).
        $orders = Auth::user()->assignedDeliveries()
            ->when($this->filter === 'pending', fn ($q) => $q->whereNotIn('status', ['delivered', 'cancelled']))
            ->when($this->filter === 'delivered', fn ($q) => $q->where('status', 'delivered'))
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('order_number', 'like', "%{$this->search}%")
                ->orWhere('customer_name', 'like', "%{$this->search}%")
                ->orWhere('customer_phone', 'like', "%{$this->search}%")))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.delivery.orders.index', [
            'orders' => $orders,
            'pendingCount' => Auth::user()->assignedDeliveries()->whereNotIn('status', ['delivered', 'cancelled'])->count(),
        ])->layout('layouts.delivery', ['title' => 'My Deliveries']);
    }
}
