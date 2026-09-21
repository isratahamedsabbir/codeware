<?php

namespace App\Livewire\Admin\Coupons;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Coupon;
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
        $coupon = Coupon::findOrFail($id);
        $newStatus = $coupon->status === 'active' ? 'inactive' : 'active';

        $coupon->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Coupon: {$coupon->code} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Coupon status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'coupon-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $coupon = Coupon::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Coupon: {$coupon->code}");
            $coupon->delete();
            $this->dispatch('notify', message: 'Coupon deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'coupon-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'coupon-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $coupons = Coupon::whereIn('id', $this->selectedIds)->get();

        foreach ($coupons as $coupon) {
            AdminActivity::log('deleted', "Coupon: {$coupon->code}");
            $coupon->delete();
        }

        $count = $coupons->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('coupon', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'coupon-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.coupons.index', [
            'coupons' => Coupon::query()
                ->with('creator')
                ->withCount('products')
                ->when($this->search, fn ($q) => $q->where('code', 'like', '%'.strtoupper($this->search).'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Coupons', 'hidePageHeading' => true]);
    }
}
