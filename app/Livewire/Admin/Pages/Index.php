<?php

namespace App\Livewire\Admin\Pages;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
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

    /**
     * 'all' or one of self::TYPES' keys. Defaults to 'page' so this screen keeps
     * showing standalone pages by default — switch it to audit/manage the
     * companion pages of products, posts, and categories from here too.
     */
    public string $typeFilter = 'page';

    public ?int $deletingId = null;

    /** Typed into the delete/bulk-delete confirmation modal — must equal "delete" before the button unlocks. */
    public string $deleteConfirmation = '';

    public ?int $viewingId = null;

    public int $puckSessionMinutes = 30;

    /** The FRONTEND_URL .env value, edited from the Settings modal (see saveFrontendUrl()). */
    public string $frontendUrl = '';

    /** The FRONTEND_PAGE_PATH .env value — see saveFrontendUrl(). */
    public string $pagePreviewPath = '';

    public function mount(): void
    {
        $this->puckSessionMinutes = Setting::puckSessionMinutes();
        $this->frontendUrl = EnvFile::get('FRONTEND_URL', '') ?? '';
        $this->pagePreviewPath = EnvFile::get('FRONTEND_PAGE_PATH', '') ?? '';
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

        $token = PuckEditor::token(auth()->user(), "puck-builder-{$pageId}");

        $url = config('cms.editor_base_url')."/puck/edit/{$page->type}/{$pageId}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    public function saveEditorSettings(): void
    {
        $this->validate([
            'puckSessionMinutes' => 'required|integer|min:1|max:1440',
        ]);

        Setting::set('puck_session_minutes', $this->puckSessionMinutes);

        AdminActivity::log('updated', "Puck editor token expiry set to {$this->puckSessionMinutes} minute(s)");

        $this->dispatch('close-modal', name: 'editor-settings');
        $this->dispatch('notify', message: 'Editor settings saved.');
    }

    /**
     * Persists the public site's base URL and the optional path segment for
     * a standalone page's Preview link, straight from this page's own
     * Settings modal — same pattern as Products\Index::saveFrontendUrl().
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
            'pagePreviewPath' => 'nullable|string|max:255|regex:/^[a-z0-9\-\/]*$/i',
        ], [], ['frontendUrl' => 'frontend URL', 'pagePreviewPath' => 'page path']);

        $this->pagePreviewPath = trim($this->pagePreviewPath, '/');

        try {
            EnvFile::set([
                'FRONTEND_URL' => $this->frontendUrl,
                'FRONTEND_PAGE_PATH' => $this->pagePreviewPath,
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
        $this->deleteConfirmation = '';
        $this->dispatch('open-modal', name: 'page-delete');
    }

    public function delete(): void
    {
        if (strtolower(trim($this->deleteConfirmation)) !== 'delete') {
            return;
        }

        if ($this->deletingId) {
            $page = Page::findOrFail($this->deletingId);
            PageCascade::deleteEntityFor($page);
            AdminActivity::log('deleted', "Page #{$page->id}: {$page->title}");
            $page->delete();
            $this->dispatch('notify', message: 'Page deleted successfully');
            $this->deletingId = null;
        }
        $this->deleteConfirmation = '';
        $this->dispatch('close-modal', name: 'page-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->deleteConfirmation = '';
        $this->dispatch('open-modal', name: 'page-bulk-delete');
    }

    public function bulkDelete(): void
    {
        if (strtolower(trim($this->deleteConfirmation)) !== 'delete') {
            return;
        }

        $pages = Page::whereIn('id', $this->selectedIds)->get();

        foreach ($pages as $page) {
            PageCascade::deleteEntityFor($page);
            AdminActivity::log('deleted', "Page #{$page->id}: {$page->title}");
            $page->delete();
        }

        $count = $pages->count();
        $this->selectedIds = [];
        $this->deleteConfirmation = '';

        $this->dispatch('notify', message: "{$count} ".Str::plural('page', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'page-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.pages.index', [
            'pages' => Page::query()
                ->with('creator')
                ->when($this->typeFilter !== 'all', fn ($q) => $q->where('type', $this->typeFilter))
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('title->en', 'like', "%{$this->search}%")
                        ->orWhere('title->bn', 'like', "%{$this->search}%")
                        ->orWhere('slug', 'like', "%{$this->search}%");
                }))
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Pages', 'hidePageHeading' => true]);
    }
}
