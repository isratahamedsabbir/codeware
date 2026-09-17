<?php

namespace App\Livewire\Admin\Tags;

use App\Concerns\HasPerPage;
use App\Models\Tag;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public ?int $deletingId = null;

    /** @var array<int, int> */
    public array $selectedIds = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $tag = Tag::findOrFail($id);
        $newStatus = $tag->status === 'active' ? 'inactive' : 'active';

        $tag->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Tag: {$tag->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Tag status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'tag-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $tag = Tag::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Tag: {$tag->name}");
            $tag->delete();
            $this->dispatch('notify', message: 'Tag deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'tag-delete');
    }

    /**
     * Ctrl/Cmd+click row selection or the row's own checkbox (see the view) —
     * toggles one tag id in/out of the bulk-selection.
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

        $this->dispatch('open-modal', name: 'tag-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $tags = Tag::whereIn('id', $this->selectedIds)->get();

        foreach ($tags as $tag) {
            AdminActivity::log('deleted', "Tag: {$tag->name}");
            $tag->delete();
        }

        $count = $tags->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('tag', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'tag-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.tags.index', [
            'tags' => Tag::query()
                ->when($this->search, fn ($q) => $q->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
                ->withCount('posts')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Tags', 'hidePageHeading' => true]);
    }
}
