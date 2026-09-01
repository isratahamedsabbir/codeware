<?php

namespace App\Livewire\Admin\Posts;

use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\PageCascade;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

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

    public function openPuckEditor(int $postId): void
    {
        $post = Post::with('page')->findOrFail($postId);

        $page = $post->page ?? Page::create([
            'user_id' => auth()->id(),
            'post_id' => $post->id,
            'type' => 'post',
            'title' => $post->title,
            'slug' => $post->slug,
            'status' => $post->status,
        ]);

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(config('app.puck_session', 5))
        )->plainTextToken;

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

    public function render()
    {
        return view('livewire.admin.posts.index', [
            'posts' => Post::query()
                ->with(['category', 'page'])
                ->when($this->search, fn ($q) => $q
                    ->where('title->en', 'like', "%{$this->search}%")
                    ->orWhere('title->bn', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate(Setting::perPage()),
        ])->layout('layouts.admin', ['title' => 'Posts']);
    }
}
