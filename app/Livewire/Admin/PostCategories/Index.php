<?php

namespace App\Livewire\Admin\BlogCategories;

use App\Models\BlogCategory;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function reorder(array $order): void
    {
        foreach ($order as $sortOrder => $categoryId) {
            BlogCategory::where('id', $categoryId)->update(['sort_order' => $sortOrder]);
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'category-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            BlogCategory::findOrFail($this->deletingId)->delete();
            $this->dispatch('notify', message: 'Category deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'category-delete');
    }

    public function render()
    {
        return view('livewire.admin.blog-categories.index', [
            'categories' => BlogCategory::query()
                ->when($this->search, fn ($q) => $q->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->withCount('posts')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(15),
        ])->layout('layouts.admin', ['title' => 'Blog Categories']);
    }
}
