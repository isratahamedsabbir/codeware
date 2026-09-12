<?php

namespace App\Livewire\Vendor\Products;

use App\Concerns\HasPerPage;
use App\Models\Product;
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

        $products = Product::withTrashed()
            ->whereIn('vendor_id', $vendorIds)
            ->with('brand')
            ->when($this->search, fn ($q) => $q->where('name->en', 'like', "%{$this->search}%"))
            ->orderByDesc('updated_at')
            ->paginate($this->perPage);

        return view('livewire.vendor.products.index', [
            'products' => $products,
        ])->layout('layouts.vendor', ['title' => 'Products']);
    }
}
