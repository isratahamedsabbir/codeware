<?php

namespace App\Livewire\Admin\ProductCategories;

use App\Models\ProductCategory;
use App\Support\AdminActivity;
use App\Support\PageCascade;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
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
        $all = ProductCategory::query()
            ->with('page')
            ->withCount('products')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tree = ProductCategory::tree($all);

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);
            $tree = $tree->filter(fn (ProductCategory $category) => str_contains(mb_strtolower($category->getTranslation('name', 'en', false) ?: ''), $needle)
                || str_contains(mb_strtolower($category->getTranslation('name', 'bn', false) ?: ''), $needle))
                ->values();
        }

        return view('livewire.admin.product-categories.index', [
            'categories' => $tree,
        ])->layout('layouts.admin', ['title' => 'Product Categories']);
    }
}
