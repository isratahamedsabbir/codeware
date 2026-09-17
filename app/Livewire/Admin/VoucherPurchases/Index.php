<?php

namespace App\Livewire\Admin\VoucherPurchases;

use App\Concerns\HasPerPage;
use App\Models\VoucherPurchase;
use App\Services\VoucherEmailService;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $viewingId = null;

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function resendEmail(int $id, VoucherEmailService $email): void
    {
        $purchase = VoucherPurchase::findOrFail($id);

        $sent = $email->sendVoucher($purchase);

        AdminActivity::log('updated', "Voucher purchase: {$purchase->code} email ".($sent ? 'resent' : 'resend failed'));

        $this->dispatch('notify', message: $sent
            ? 'Voucher email resent successfully'
            : 'Could not send the voucher email — check the mail settings.');
    }

    public function render()
    {
        return view('livewire.admin.voucher-purchases.index', [
            'purchases' => VoucherPurchase::query()
                ->with('voucher')
                ->when($this->search, function ($q) {
                    $term = '%'.$this->search.'%';
                    $q->where(fn ($q) => $q
                        ->where('code', 'like', '%'.strtoupper($this->search).'%')
                        ->orWhere('customer_email', 'like', $term)
                        ->orWhere('customer_name', 'like', $term));
                })
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderByDesc('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Voucher Sales', 'hidePageHeading' => true]);
    }
}
