<?php

namespace App\Livewire\Admin\PostCategories;

use App\Models\PostCategory;
use App\Support\AdminActivity;
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
            PostCategory::where('id', $categoryId)->update(['sort_order' => $sortOrder]);
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
            $category = PostCategory::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Post Category: {$category->name}");
            $category->delete();
            $this->dispatch('notify', message: 'Category deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'category-delete');
    }

    public function render()
    {
        return view('livewire.admin.post-categories.index', [
            'categories' => PostCategory::query()
                ->when($this->search, fn ($q) => $q->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->with('page')
                ->withCount('posts')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(15),
        ])->layout('layouts.admin', ['title' => 'Post Categories']);
    }
}
