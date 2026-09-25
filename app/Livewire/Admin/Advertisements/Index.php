<?php

namespace App\Livewire\Admin\Advertisements;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Advertisement;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public ?int $deletingId = null;

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'advertisement-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $ad = Advertisement::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Advertisement: {$ad->name}");
            $ad->delete();
            $this->dispatch('notify', message: 'Advertisement deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'advertisement-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'advertisement-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $ads = Advertisement::whereIn('id', $this->selectedIds)->get();

        foreach ($ads as $ad) {
            AdminActivity::log('deleted', "Advertisement: {$ad->name}");
            $ad->delete();
        }

        $count = $ads->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('advertisement', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'advertisement-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.advertisements.index', [
            'advertisements' => Advertisement::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('code', 'like', "%{$this->search}%"))
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Advertisements', 'hidePageHeading' => true]);
    }
}
