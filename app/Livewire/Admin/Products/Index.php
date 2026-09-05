<?php

namespace App\Livewire\Admin\Products;

use App\Concerns\HasPerPage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\PageCascade;
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
            'slug' => $product->slug,
            'status' => $product->status,
        ]);

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(Setting::puckSessionMinutes())
        )->plainTextToken;

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

    public function render()
    {
        return view('livewire.admin.products.index', [
            'products' => Product::query()
                ->with(['category', 'page'])
                ->when($this->search, fn ($q) => $q
                    ->where('name->en', 'like', "%{$this->search}%")
                    ->orWhere('name->bn', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Products']);
    }
}
