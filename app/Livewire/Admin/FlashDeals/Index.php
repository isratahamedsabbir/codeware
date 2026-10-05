<?php

namespace App\Livewire\Admin\FlashDeals;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\FlashDeal;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public ?int $deletingId = null;

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $deal = FlashDeal::findOrFail($id);
        $newStatus = $deal->status === 'active' ? 'inactive' : 'active';

        $deal->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Flash deal: {$deal->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Flash deal status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'flash-deal-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $deal = FlashDeal::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Flash deal: {$deal->name}");
            $deal->delete();
            $this->dispatch('notify', message: 'Flash deal deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'flash-deal-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'flash-deal-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $deals = FlashDeal::whereIn('id', $this->selectedIds)->get();

        foreach ($deals as $deal) {
            AdminActivity::log('deleted', "Flash deal: {$deal->name}");
            $deal->delete();
        }

        $count = $deals->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('flash deal', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'flash-deal-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.flash-deals.index', [
            'deals' => FlashDeal::query()
                ->with('creator')
                ->withCount('products')
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Flash Deals', 'hidePageHeading' => true]);
    }
}
