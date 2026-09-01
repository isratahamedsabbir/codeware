<?php

namespace App\Livewire\Admin\Tags;

use App\Concerns\HasPerPage;
use App\Models\Tag;
use App\Support\AdminActivity;
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

    public function render()
    {
        return view('livewire.admin.tags.index', [
            'tags' => Tag::query()
                ->when($this->search, fn ($q) => $q->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->withCount('posts')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Post Tags']);
    }
}
