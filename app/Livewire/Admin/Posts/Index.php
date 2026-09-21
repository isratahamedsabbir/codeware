<?php

namespace App\Livewire\Admin\Posts;

use App\Concerns\HasBulkSelection;
use App\Concerns\HasPerPage;
use App\Concerns\WithSearch;
use App\Models\Page;
use App\Models\Post;
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

    /** The FRONTEND_POST_PATH .env value — see saveFrontendUrl(). */
    public string $postPreviewPath = '';

    public function mount(): void
    {
        $this->frontendUrl = EnvFile::get('FRONTEND_URL', '') ?? '';
        $this->postPreviewPath = EnvFile::get('FRONTEND_POST_PATH', '') ?? '';
    }

    /**
     * Persists the public site's base URL and the optional path segment for
     * this page's own Preview links, straight from this page's Settings
     * modal — same pattern as Products\Index::saveFrontendUrl().
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
            'postPreviewPath' => 'nullable|string|max:255|regex:/^[a-z0-9\-\/]*$/i',
        ], [], ['frontendUrl' => 'frontend URL', 'postPreviewPath' => 'post path']);

        $this->postPreviewPath = trim($this->postPreviewPath, '/');

        try {
            EnvFile::set([
                'FRONTEND_URL' => $this->frontendUrl,
                'FRONTEND_POST_PATH' => $this->postPreviewPath,
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

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openPuckEditor(int $postId): void
    {
        $post = Post::with('page')->findOrFail($postId);

        $page = $post->page ?? Page::create([
            'user_id' => auth()->id(),
            'post_id' => $post->id,
            'type' => 'post',
            'title' => $post->title,
            'status' => $post->status,
        ]);

        $token = PuckEditor::token(auth()->user(), "puck-builder-{$page->id}");

        $url = config('cms.editor_base_url')."/puck/edit/post/{$page->id}#token={$token}";
        $this->js('window.open('.json_encode($url).', \'_blank\')');
    }

    public function toggleStatus(int $id): void
    {
        $post = Post::with('page')->findOrFail($id);
        $newStatus = $post->status === 'active' ? 'inactive' : 'active';

        $post->update([
            'status' => $newStatus,
            // Fixed at first publish — reactivating a previously-published post
            // shouldn't reset "when was this first published".
            'published_at' => $newStatus === 'active' ? ($post->published_at ?? now()) : $post->published_at,
        ]);
        $post->page?->update(['status' => $newStatus]);

        AdminActivity::log('updated', "Post #{$post->id}: {$post->title} ".($newStatus === 'active' ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: 'Post status updated');
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
        $this->dispatch('open-modal', name: 'post-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $post = Post::with('page')->findOrFail($this->deletingId);
            PageCascade::deletePageFor($post);
            AdminActivity::log('deleted', "Post #{$post->id}: {$post->title}");
            $post->delete();
            $this->dispatch('notify', message: 'Post deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'post-delete');
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'post-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $posts = Post::with('page')->whereIn('id', $this->selectedIds)->get();

        foreach ($posts as $post) {
            PageCascade::deletePageFor($post);
            AdminActivity::log('deleted', "Post #{$post->id}: {$post->title}");
            $post->delete();
        }

        $count = $posts->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('post', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'post-bulk-delete');
    }

    public function render()
    {
        return view('livewire.admin.posts.index', [
            'posts' => Post::query()
                ->with(['category', 'page'])
                ->when($this->search, fn ($q) => $q
                    ->where('title->en', 'like', "%{$this->search}%")
                    ->orWhere('title->bn', 'like', "%{$this->search}%")
                    ->orWhereHas('page', fn ($p) => $p->where('slug', 'like', "%{$this->search}%")))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Posts', 'hidePageHeading' => true]);
    }
}
