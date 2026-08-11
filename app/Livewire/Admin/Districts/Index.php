<?php

namespace App\Livewire\Admin\Districts;

use App\Models\District;
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

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'district-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            District::findOrFail($this->deletingId)->delete();
            $this->dispatch('notify', message: 'District deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'district-delete');
    }

    public function render()
    {
        return view('livewire.admin.districts.index', [
            'districts' => District::query()
                ->when($this->search, fn ($q) => $q
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->withCount('upazilas')
                ->orderBy('sort_order')
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => 'Districts']);
    }
}
