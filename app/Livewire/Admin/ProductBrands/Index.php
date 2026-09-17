<?php

namespace App\Livewire\Admin\ProductBrands;

use App\Concerns\HasPerPage;
use App\Models\ProductBrand;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public ?int $deletingId = null;

    /** @var array<int, int> */
    public array $selectedIds = [];

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

    /**
     * Ctrl/Cmd+click row selection or the row's own checkbox (see the view) —
     * toggles one brand id in/out of the bulk-selection.
     */
    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));

            return;
        }

        $this->selectedIds[] = $id;
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'product-brand-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $brands = ProductBrand::whereIn('id', $this->selectedIds)->get();

        foreach ($brands as $brand) {
            AdminActivity::log('deleted', "Product Brand: {$brand->name}");
            $brand->delete();
        }

        $count = $brands->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('brand', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'product-brand-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.product-brands.index', [
            'productBrands' => ProductBrand::query()
                ->with('creator')
                ->when($this->search, fn ($q) => $q->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%"))
                ->orderBy('sort_order')
                ->orderBy('name->en')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Product Brands', 'hidePageHeading' => true]);
    }
}
