<?php

namespace App\Livewire\Vendor\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    /**
     * The order's id, never the Order itself.
     *
     * This used to be `public Order $order`, which Livewire rehydrates on every
     * subsequent request by key — so the scope check in mount() ran once, on
     * first load, and never again. Swapping the id in the wire payload was
     * enough to have another vendor's order rendered: customer name, email,
     * phone and shipping address. The same shape of bug is why
     * Delivery\Orders\Show holds an int and re-scopes in findOrder().
     *
     * Order and items are re-fetched through the vendor scope on each render
     * instead.
     */
    public int $orderId;

    /**
     * 404s (not 403s) when none of this order's items belong to the current
     * user's vendors — confirming the order *exists* but isn't theirs would
     * leak information a vendor has no business knowing.
     */
    public function mount(int $orderId): void
    {
        $this->orderId = $this->findOrder($orderId)->id;
    }

    /** @return Collection<int, OrderItem> */
    private function items(Order $order): Collection
    {
        return $order->items()
            ->whereHas('product', fn ($q) => $q->whereIn('vendor_id', Auth::user()->vendors()->pluck('product_vendors.id')))
            ->get();
    }

    private function findOrder(int $orderId): Order
    {
        $vendorIds = Auth::user()->vendors()->pluck('product_vendors.id');

        $order = Order::whereHas('items.product', fn ($q) => $q->whereIn('vendor_id', $vendorIds))
            ->findOrFail($orderId);

        abort_if($this->items($order)->isEmpty(), 404);

        return $order;
    }

    public function render()
    {
        $order = $this->findOrder($this->orderId);

        return view('livewire.vendor.orders.show', [
            'order' => $order,
            'items' => $this->items($order),
        ])->layout('layouts.vendor', ['title' => "Order {$order->order_number}"]);
    }
}
