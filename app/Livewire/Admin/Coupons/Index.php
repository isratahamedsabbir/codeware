<?php

namespace App\Livewire\Admin\Coupons;

use App\Concerns\HasPerPage;
use App\Models\Coupon;
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

    public function render()
    {
        return view('livewire.admin.coupons.index', [
            'coupons' => Coupon::query()
                ->when($this->search, fn ($q) => $q->where('code', 'like', '%'.strtoupper($this->search).'%'))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Coupons']);
    }
}
