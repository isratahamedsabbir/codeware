<?php

namespace App\Livewire\Admin\PostCategories;

use App\Concerns\HasPerPage;
use App\Models\PostCategory;
use App\Support\AdminActivity;
use App\Support\PageCascade;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    /** @var array<int, int> */
    public array $selectedIds = [];

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

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

    /**
     * Ctrl/Cmd+click row selection or the row's own checkbox (see the view) —
     * toggles one category id in/out of the bulk-selection.
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

        $this->dispatch('open-modal', name: 'post-category-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $categories = PostCategory::with('page')->whereIn('id', $this->selectedIds)->get();

        foreach ($categories as $category) {
            PageCascade::deletePageFor($category, forcePage: true);
            AdminActivity::log('deleted', "Post Category: {$category->name}");
            $category->delete();
        }

        $count = $categories->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('category', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'post-category-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.post-categories.index', [
            'categories' => PostCategory::query()
                ->when($this->search, fn ($q) => $q->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->with(['page', 'creator'])
                ->withCount('posts')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Post Categories', 'hidePageHeading' => true]);
    }
}
