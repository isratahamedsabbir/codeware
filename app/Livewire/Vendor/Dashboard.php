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

        return view('livewire.vendor.dashboard', [
            'vendors' => $vendors,
            'productsCount' => Product::whereIn('vendor_id', $vendorIds)->count(),
            'ordersCount' => Order::whereHas('items.product', fn ($q) => $q->whereIn('vendor_id', $vendorIds))->count(),
        ])->layout('layouts.vendor', ['title' => 'Dashboard']);
    }
}
