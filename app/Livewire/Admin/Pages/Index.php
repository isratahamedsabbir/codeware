<?php

namespace App\Livewire\Admin\Pages;

use App\Concerns\HasPerPage;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\AdminActivity;
use App\Support\PageCascade;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    /**
     * Human-readable labels for every Page::$type value — used for the type
     * filter dropdown and the type badge in the table.
     */
    public const TYPES = [
        'page' => 'Page',
        'post' => 'Post',
        'product' => 'Product',
        'product_category' => 'Product Category',
        'post_category' => 'Post Category',
    ];

    public string $search = '';

    /**
     * 'all' or one of self::TYPES' keys. Defaults to 'page' so this screen keeps
     * showing standalone pages by default — switch it to audit/manage the
     * companion pages of products, posts, and categories from here too.
     */
    public string $typeFilter = 'page';

    public ?int $deletingId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function reorder(array $order): void
    {
        foreach ($order as $sortOrder => $pageId) {
            Page::where('id', $pageId)->update(['sort_order' => $sortOrder]);
        }
    }

    public function openPuckEditor(int $pageId): void
    {
        $page = Page::findOrFail($pageId);

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken('puck-builder', ['*'], now()->addMinutes(config('app.puck_session', 5)))->plainTextToken;

        $url = config('cms.editor_base_url')."/puck/edit/{$page->type}/{$pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    /**
     * Site-wide header/footer layout editor (moved here from Settings — this is
     * where pages live, not a per-page thing like openPuckEditor() above).
     */
    public function getLayoutEditorUrl(string $type): string
    {
        $token = auth()->user()->createToken('builder')->plainTextToken;
        $baseUrl = config('cms.editor_base_url', 'http://localhost:3000');

        return "{$baseUrl}/editor?mode=layout&type={$type}&token={$token}";
    }

    /**
     * Flips this Page's status and, for a linked page (type != 'page'), the
     * paired Product/Post/Category too — keeping both sides in sync the same
     * way each entity's own form does.
     */
    public function toggleStatus(int $id): void
    {
        $page = Page::findOrFail($id);
        $newStatus = $page->status === 'active' ? 'inactive' : 'active';

        $page->update(['status' => $newStatus]);

        $entity = match ($page->type) {
            'product' => Product::find($page->product_id),
            'post' => Post::find($page->post_id),
            'product_category' => ProductCategory::find($page->category_id),
            'post_category' => PostCategory::find($page->category_id),
            default => null,
        };
        $entity?->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Page #{$page->id}: {$page->title} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Page status updated');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'page-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $page = Page::findOrFail($this->deletingId);
            PageCascade::deleteEntityFor($page);
            AdminActivity::log('deleted', "Page #{$page->id}: {$page->title}");
            $page->delete();
            $this->dispatch('notify', message: 'Page deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'page-delete');
    }

    public function render()
    {
        return view('livewire.admin.pages.index', [
            'pages' => Page::query()
                ->when($this->typeFilter !== 'all', fn ($q) => $q->where('type', $this->typeFilter))
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('title->en', 'like', "%{$this->search}%")
                        ->orWhere('title->bn', 'like', "%{$this->search}%")
                        ->orWhere('slug', 'like', "%{$this->search}%");
                }))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Pages']);
    }
}
