<?php

namespace App\Livewire\Admin\ProductVendors;

use App\Concerns\HasPerPage;
use App\Models\ProductVendor;
use App\Support\AdminActivity;
use App\Support\EnvFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class Index extends Component
{
    use HasPerPage, WithPagination;

    public string $search = '';

    public ?int $deletingId = null;

    /** The VENDOR_URL .env value, edited from the Settings modal (see saveVendorUrl()). */
    public string $vendorUrl = '';

    public function mount(): void
    {
        $this->vendorUrl = EnvFile::get('VENDOR_URL', '') ?? '';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Persists the Vendor Portal's subdomain (see bootstrap/app.php) straight from
     * this page's Settings modal, rather than sending the admin off to the full
     * Settings → Env tab for a single field.
     */
    public function saveVendorUrl(): void
    {
        // The trigger button/modal are hidden from staff in the Blade view (this
        // page's route only requires access-admin, not access-admin-system), but
        // a Livewire component's public methods are still directly callable —
        // this is the actual enforcement, not the hidden UI.
        Gate::authorize('access-admin-system');

        $this->validate(['vendorUrl' => 'nullable|url'], [], ['vendorUrl' => 'vendor portal URL']);

        try {
            EnvFile::set(['VENDOR_URL' => $this->vendorUrl]);
        } catch (RuntimeException $e) {
            $this->dispatch('notify', message: 'Could not save the vendor portal URL: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        // VENDOR_URL controls which host the Vendor Portal route group binds to
        // — if routes are ever cached (route:cache, as a production deploy
        // might run), that cache would keep serving the old host otherwise.
        Artisan::call('route:clear');

        AdminActivity::log('updated', 'Vendor portal URL updated');

        $this->dispatch('close-modal', name: 'vendor-url-settings');
        $this->dispatch('notify', message: 'Vendor portal URL saved.');
    }

    public function toggleStatus(int $id): void
    {
        $vendor = ProductVendor::findOrFail($id);
        $newStatus = $vendor->status === 'active' ? 'inactive' : 'active';

        $vendor->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Product Vendor: {$vendor->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Vendor status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'product-vendor-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $vendor = ProductVendor::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Product Vendor: {$vendor->name}");
            $vendor->delete();
            $this->dispatch('notify', message: 'Vendor deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'product-vendor-delete');
    }

    public function render()
    {
        return view('livewire.admin.product-vendors.index', [
            'productVendors' => ProductVendor::query()
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Product Vendors']);
    }
}
