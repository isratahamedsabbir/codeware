<?php

namespace App\Livewire\Admin\Categories;

use App\Models\Category;
use App\Support\AdminActivity;
use App\Support\PageCascade;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    public string $search = '';

    /**
     * Which pool is shown — Product or Post categories. Defaults to Product
     * (the more commonly managed one). Deliberately not "both at once": the
     * two pools have unrelated parent_id trees, so mixing them in one
     * flat/indented list would misrepresent the hierarchy. #[Url] so the
     * Form's "back to list" redirect (?type=...) lands back on the same pool
     * it was creating/editing in.
     */
    #[Url]
    public string $typeFilter = Category::TYPE_PRODUCT;

    public string $statusFilter = '';

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    /** @var array<int, int> */
    public array $selectedIds = [];

    public function updatedTypeFilter(): void
    {
        $this->selectedIds = [];
        $this->viewingId = null;
    }

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
            Category::where('id', $categoryId)->where('type', $this->typeFilter)->update(['sort_order' => $sortOrder]);
        }
    }

    public function toggleStatus(int $id): void
    {
        $category = Category::with('page')->findOrFail($id);
        $newStatus = $category->status === 'active' ? 'inactive' : 'active';

        $category->update(['status' => $newStatus]);
        $category->page?->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Category: {$category->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
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
            $category = Category::with('page')->findOrFail($this->deletingId);
            PageCascade::deletePageFor($category, forcePage: true);
            AdminActivity::log('deleted', "Category: {$category->name}");
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

        $this->dispatch('open-modal', name: 'category-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $categories = Category::with('page')->whereIn('id', $this->selectedIds)->get();

        foreach ($categories as $category) {
            PageCascade::deletePageFor($category, forcePage: true);
            AdminActivity::log('deleted', "Category: {$category->name}");
            $category->delete();
        }

        $count = $categories->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('category', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'category-bulk-delete');
    }

    public function render()
    {
        $all = Category::query()
            ->where('type', $this->typeFilter)
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->with(['page', 'creator'])
            ->withCount(['products', 'posts'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tree = Category::tree($all);

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);
            $tree = $tree->filter(fn (Category $category) => str_contains(mb_strtolower($category->getTranslation('name', 'en', false) ?: ''), $needle)
                || str_contains(mb_strtolower($category->getTranslation('name', 'bn', false) ?: ''), $needle))
                ->values();
        }

        return view('livewire.admin.categories.index', [
            'categories' => $tree,
        ])->layout('layouts.admin', ['title' => 'Categories', 'hidePageHeading' => true]);
    }
}
