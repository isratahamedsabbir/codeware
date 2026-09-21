<?php

namespace App\Livewire\Admin\Products;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Page;
use App\Models\Product;
use App\Support\AdminActivity;
use App\Support\EnvFile;
use App\Support\PageCascade;
use App\Support\PuckEditor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class Index extends Component
{
    use HasBulkSelection, HasPerPage, WithPagination, WithSearch;

    public string $statusFilter = '';

    public ?int $deletingId = null;

    public ?int $viewingId = null;

    /** The FRONTEND_URL .env value, edited from the Settings modal (see saveFrontendUrl()). */
    public string $frontendUrl = '';

    /** The FRONTEND_PRODUCT_PATH .env value — see saveFrontendUrl(). */
    public string $productPreviewPath = '';

    public function mount(): void
    {
        $this->frontendUrl = EnvFile::get('FRONTEND_URL', '') ?? '';
        $this->productPreviewPath = EnvFile::get('FRONTEND_PRODUCT_PATH', '') ?? '';
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    /**
     * Persists the public site's base URL (used to build product links in
     * emails/sitemaps — see Product::product_url) and the optional path
     * segment for this page's own Preview links, straight from this page's
     * Settings modal, rather than sending the admin off to the full
     * Settings → Env tab for a couple of fields.
     */
    public function saveFrontendUrl(): void
    {
        // The trigger button/modal are hidden from staff in the Blade view (this
        // page's route only requires access-admin, not access-admin-system), but
        // a Livewire component's public methods are still directly callable —
        // this is the actual enforcement, not the hidden UI.
        Gate::authorize('access-admin-system');

        $this->validate([
            'frontendUrl' => 'nullable|url',
            'productPreviewPath' => 'nullable|string|max:255|regex:/^[a-z0-9\-\/]*$/i',
        ], [], ['frontendUrl' => 'frontend URL', 'productPreviewPath' => 'product path']);

        $this->productPreviewPath = trim($this->productPreviewPath, '/');

        try {
            EnvFile::set([
                'FRONTEND_URL' => $this->frontendUrl,
                'FRONTEND_PRODUCT_PATH' => $this->productPreviewPath,
            ]);
        } catch (RuntimeException $e) {
            $this->dispatch('notify', message: 'Could not save the frontend URL: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        AdminActivity::log('updated', 'Frontend URL updated');

        $this->dispatch('close-modal', name: 'frontend-url-settings');
        $this->dispatch('notify', message: 'Frontend URL saved.');
    }

    public function reorder(array $order): void
    {
        foreach ($order as $sortOrder => $productId) {
            Product::where('id', $productId)->update(['sort_order' => $sortOrder]);
        }
    }

    public function openPuckEditor(int $productId): void
    {
        $product = Product::with('page')->findOrFail($productId);

        $page = $product->page ?? Page::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'type' => 'product',
            'title' => $product->name,
            'status' => $product->status,
        ]);

        $token = PuckEditor::token(auth()->user(), "puck-builder-{$page->id}");

        $url = config('cms.editor_base_url')."/puck/edit/product/{$page->id}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    public function toggleStatus(int $id): void
    {
        $product = Product::with('page')->findOrFail($id);
        $newStatus = $product->status === 'active' ? 'inactive' : 'active';

        $product->update(['status' => $newStatus]);
        $product->page?->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Product #{$product->id}: {$product->name} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Product status updated');
    }

    public function toggleFeatured(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_featured' => ! $product->is_featured]);

        AdminActivity::log('updated', "Product #{$product->id}: {$product->name} ".($product->is_featured ? 'marked featured' : 'unmarked featured'));
        $this->dispatch('notify', message: 'Product featured status updated');
    }

    public function toggleUpcoming(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['is_upcoming' => ! $product->is_upcoming]);

        AdminActivity::log('updated', "Product #{$product->id}: {$product->name} ".($product->is_upcoming ? 'marked upcoming' : 'unmarked upcoming'));
        $this->dispatch('notify', message: 'Product upcoming status updated');
    }

    public function toggleChargeShipping(int $id): void
    {
        $product = Product::findOrFail($id);
        $product->update(['charge_shipping' => ! $product->charge_shipping]);

        AdminActivity::log('updated', "Product #{$product->id}: {$product->name} ".($product->charge_shipping ? 'shipping charge enabled' : 'shipping charge disabled'));
        $this->dispatch('notify', message: 'Product shipping charge updated');
    }

    public function viewDetails(int $id): void
    {
        $this->viewingId = $id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'product-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $product = Product::with('page')->findOrFail($this->deletingId);
            PageCascade::deletePageFor($product);
            AdminActivity::log('deleted', "Product #{$product->id}: {$product->name}");
            $product->delete();
            $this->dispatch('notify', message: 'Product deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'product-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'product-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $products = Product::with('page')->whereIn('id', $this->selectedIds)->get();

        foreach ($products as $product) {
            PageCascade::deletePageFor($product);
            AdminActivity::log('deleted', "Product #{$product->id}: {$product->name}");
            $product->delete();
        }

        $count = $products->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('product', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'product-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.products.index', [
            'products' => Product::query()
                ->with(['categories', 'page', 'creator'])
                ->when($this->search, fn ($q) => $q
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%")
                    ->orWhereHas('page', fn ($p) => $p->where('slug', 'like', "%{$this->search}%")))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Products', 'hidePageHeading' => true]);
    }
}
