<?php

namespace App\Livewire\Admin\ProductCategories;

use App\Models\ProductCategory;
use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\PageCascade;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function reorder(array $order): void
    {
        foreach ($order as $sortOrder => $categoryId) {
            ProductCategory::where('id', $categoryId)->update(['sort_order' => $sortOrder]);
        }
    }

    public function toggleStatus(int $id): void
    {
        $category = ProductCategory::with('page')->findOrFail($id);
        $newStatus = $category->status === 'active' ? 'inactive' : 'active';

        $category->update(['status' => $newStatus]);
        $category->page?->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Product Category: {$category->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Category status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'product-category-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $category = ProductCategory::with('page')->findOrFail($this->deletingId);
            PageCascade::deletePageFor($category, forcePage: true);
            AdminActivity::log('deleted', "Product Category: {$category->name}");
            $category->delete();
            $this->dispatch('notify', message: 'Category deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'product-category-delete');
    }

    public function render()
    {
        return view('livewire.admin.product-categories.index', [
            'categories' => ProductCategory::query()
                ->when($this->search, fn ($q) => $q
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%"))
                ->with('page')
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(Setting::perPage()),
        ])->layout('layouts.admin', ['title' => 'Product Categories']);
    }
}
