<?php

namespace App\Livewire\Admin\Videos;

use App\Models\Video;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $deletingId = null;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function reorder(array $order): void
    {
        foreach ($order as $sortOrder => $videoId) {
            Video::where('id', $videoId)->update(['sort_order' => $sortOrder]);
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'video-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Video::findOrFail($this->deletingId)->delete();
            $this->dispatch('notify', message: 'Video deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'video-delete');
    }

    public function render()
    {
        return view('livewire.admin.videos.index', [
            'videos' => Video::query()
                ->when($this->search, fn ($q) => $q
                    ->where('title->en', 'like', "%{$this->search}%")
                    ->orWhere('title->bn', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => 'Videos']);
    }
}
