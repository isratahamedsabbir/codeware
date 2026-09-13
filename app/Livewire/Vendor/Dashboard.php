<?php

namespace App\Livewire\Vendor;

use App\Models\Order;
use App\Models\Product;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $vendors = auth()->user()->vendors;
        $vendorIds = $vendors->pluck('id');

        $productsQuery = Product::whereIn('vendor_id', $vendorIds);
        $ordersQuery = Order::whereHas('items.product', fn ($q) => $q->whereIn('vendor_id', $vendorIds));

        return view('livewire.vendor.dashboard', [
            'vendors' => $vendors,
            'productsCount' => (clone $productsQuery)->count(),
            'activeProductsCount' => (clone $productsQuery)->where('status', 'active')->count(),
            'outOfStockCount' => (clone $productsQuery)->where('quantity', '<=', 0)->count(),
            'ordersCount' => (clone $ordersQuery)->count(),
            'recentOrders' => (clone $ordersQuery)
                ->with(['items' => fn ($q) => $q->whereHas('product', fn ($q2) => $q2->whereIn('vendor_id', $vendorIds))])
                ->latest()
                ->take(5)
                ->get(),
        ])->layout('layouts.vendor', ['title' => 'Dashboard', 'hideHeading' => true]);
    }
}
