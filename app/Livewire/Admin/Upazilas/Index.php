<?php

namespace App\Livewire\Admin\Upazilas;

use App\Models\District;
use App\Models\Upazila;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $districtFilter = '';
    public string $statusFilter = '';
    public ?int $deletingId = null;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedDistrictFilter(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    #[Computed]
    public function districts()
    {
        return District::orderBy('sort_order')->get();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'upazila-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Upazila::findOrFail($this->deletingId)->delete();
            $this->dispatch('notify', message: 'Upazila deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'upazila-delete');
    }

    public function render()
    {
        return view('livewire.admin.upazilas.index', [
            'upazilas' => Upazila::query()
                ->with('district')
                ->when($this->search, fn ($q) => $q
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%"))
                ->when($this->districtFilter, fn ($q) => $q->where('district_id', $this->districtFilter))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => 'Upazilas']);
    }
}
