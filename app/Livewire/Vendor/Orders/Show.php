<?php

namespace App\Livewire\Vendor\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public Order $order;

    /** @var Collection<int, OrderItem> */
    public $items;

    /**
     * 404s (not 403s) when none of this order's items belong to the current
     * user's vendors — confirming the order *exists* but isn't theirs would
     * leak information a vendor has no business knowing.
     */
    public function mount(int $orderId): void
    {
        $vendorIds = Auth::user()->vendors()->pluck('product_vendors.id');

        $this->order = Order::findOrFail($orderId);

        $this->items = $this->order->items()
            ->whereHas('product', fn ($q) => $q->whereIn('vendor_id', $vendorIds))
            ->get();

        abort_if($this->items->isEmpty(), 404);
    }

    public function render()
    {
        return view('livewire.vendor.orders.show')
            ->layout('layouts.vendor', ['title' => "Order {$this->order->order_number}"]);
    }
}
