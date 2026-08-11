<?php

namespace App\Livewire\Admin\Dealers;

use App\Models\Dealer;
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
        foreach ($order as $sortOrder => $dealerId) {
            Dealer::where('id', $dealerId)->update(['sort_order' => $sortOrder]);
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'dealer-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Dealer::findOrFail($this->deletingId)->delete();
            $this->dispatch('notify', message: 'Dealer deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'dealer-delete');
    }

    public function render()
    {
        return view('livewire.admin.dealers.index', [
            'dealers' => Dealer::query()
                ->when($this->search, fn ($q) => $q
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%")
                    ->orWhere('district', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->withCount('categories')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => 'Dealers']);
    }
}
