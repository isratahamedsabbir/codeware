<?php

namespace App\Livewire\Admin\ProductVendors;

use App\Concerns\HasPerPage;
use App\Models\ProductVendor;
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
        $vendor = ProductVendor::findOrFail($id);
        $newStatus = $vendor->status === 'active' ? 'inactive' : 'active';

        $vendor->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Product Vendor: {$vendor->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Vendor status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'product-vendor-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $vendor = ProductVendor::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Product Vendor: {$vendor->name}");
            $vendor->delete();
            $this->dispatch('notify', message: 'Vendor deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'product-vendor-delete');
    }

    public function render()
    {
        return view('livewire.admin.product-vendors.index', [
            'productVendors' => ProductVendor::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Product Vendors']);
    }
}
