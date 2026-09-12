<?php

namespace App\Livewire\Admin\Products;

use App\Concerns\HasPerPage;
use App\Models\Order;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use HasPerPage, WithPagination;

    public int $productId;

    public string $search = '';

    public function mount(int $id): void
    {
        $this->productId = Product::withTrashed()->findOrFail($id)->id;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $product = Product::withTrashed()
            ->with(['categories.page', 'brand', 'vendor', 'page'])
            ->findOrFail($this->productId);

        // Each order is loaded with only *this* product's own line item (not
        // the order's other items), since all we need here is how many of
        // this product and for how much — the order's own show page has the
        // full item breakdown.
        $orders = Order::whereHas('items', fn ($q) => $q->where('product_id', $this->productId))
            ->with(['items' => fn ($q) => $q->where('product_id', $this->productId)])
            ->when($this->search, fn ($q) => $q->where('order_number', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at')
            ->paginate($this->perPage);

        return view('livewire.admin.products.show', [
            'product' => $product,
            'orders' => $orders,
        ])->layout('layouts.admin', ['title' => $product->getTranslation('name', 'en', false)]);
    }
}
