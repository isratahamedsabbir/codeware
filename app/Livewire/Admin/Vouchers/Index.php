<?php

namespace App\Livewire\Admin\Vouchers;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Voucher;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $voucher = Voucher::findOrFail($id);
        $newStatus = $voucher->status === 'active' ? 'inactive' : 'active';

        $voucher->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Voucher: {$voucher->slug} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Voucher status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'voucher-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $voucher = Voucher::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Voucher: {$voucher->slug}");
            $voucher->delete();
            $this->dispatch('notify', message: 'Voucher deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'voucher-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'voucher-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $vouchers = Voucher::whereIn('id', $this->selectedIds)->get();

        foreach ($vouchers as $voucher) {
            AdminActivity::log('deleted', "Voucher: {$voucher->slug}");
            $voucher->delete();
        }

        $count = $vouchers->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('voucher', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'voucher-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.vouchers.index', [
            'vouchers' => Voucher::query()
                ->with('creator')
                ->withCount('purchases')
                ->when($this->search, fn ($q) => $q->where('slug', 'like', '%'.Str::slug($this->search).'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Gift Vouchers', 'hidePageHeading' => true]);
    }
}
