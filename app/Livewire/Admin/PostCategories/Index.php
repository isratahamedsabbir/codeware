<?php

namespace App\Livewire\Admin\PostCategories;

use App\Concerns\HasPerPage;
use App\Models\PostCategory;
use App\Support\AdminActivity;
use App\Support\PageCascade;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

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

    public function toggleStatus(int $id): void
    {
        $category = PostCategory::with('page')->findOrFail($id);
        $newStatus = $category->status === 'active' ? 'inactive' : 'active';

        $category->update(['status' => $newStatus]);
        $category->page?->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Post Category: {$category->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Category status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'category-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $category = PostCategory::with('page')->findOrFail($this->deletingId);
            PageCascade::deletePageFor($category, forcePage: true);
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
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Post Categories']);
    }
}
