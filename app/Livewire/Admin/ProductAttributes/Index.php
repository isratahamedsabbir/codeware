<?php

namespace App\Livewire\Admin\ProductAttributes;

use App\Concerns\HasPerPage;
use App\Models\ProductAttribute;
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

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'product-attribute-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $attribute = ProductAttribute::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Product Attribute: {$attribute->name}");
            $attribute->delete();
            $this->dispatch('notify', message: 'Attribute deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'product-attribute-delete');
    }

    /**
     * Ctrl/Cmd+click row selection or the row's own checkbox (see the view) —
     * toggles one attribute id in/out of the bulk-selection.
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

        $this->dispatch('open-modal', name: 'product-attribute-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $attributes = ProductAttribute::whereIn('id', $this->selectedIds)->get();

        foreach ($attributes as $attribute) {
            AdminActivity::log('deleted', "Product Attribute: {$attribute->name}");
            $attribute->delete();
        }

        $count = $attributes->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('attribute', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'product-attribute-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.product-attributes.index', [
            'productAttributes' => ProductAttribute::query()
                ->with('creator')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Product Attributes', 'hidePageHeading' => true]);
    }
}
