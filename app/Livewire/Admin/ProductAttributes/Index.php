<?php

namespace App\Livewire\Admin\ProductAttributes;

use App\Concerns\HasPerPage;
use App\Models\ProductAttribute;
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

    public function render()
    {
        return view('livewire.admin.product-attributes.index', [
            'productAttributes' => ProductAttribute::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Product Attributes']);
    }
}
