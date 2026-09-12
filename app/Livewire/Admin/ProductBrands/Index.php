<?php

namespace App\Livewire\Admin\ProductBrands;

use App\Concerns\HasPerPage;
use App\Models\ProductBrand;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $brand = ProductBrand::findOrFail($id);
        $newStatus = $brand->status === 'active' ? 'inactive' : 'active';

        $brand->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Product Brand: {$brand->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Brand status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'product-brand-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $brand = ProductBrand::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Product Brand: {$brand->name}");
            $brand->delete();
            $this->dispatch('notify', message: 'Brand deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'product-brand-delete');
    }

    public function render()
    {
        return view('livewire.admin.product-brands.index', [
            'productBrands' => ProductBrand::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Product Brands']);
    }
}
