<?php

namespace App\Livewire\Admin\Posts;

use App\Models\Page;
use App\Models\Post;
use App\Support\AdminActivity;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $deletingId = null;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }

    public function openPuckEditor(int $postId): void
    {
        $post = Post::with('page')->findOrFail($postId);

        $page = $post->page ?? Page::create([
            'user_id' => auth()->id(),
            'post_id' => $post->id,
            'type'    => 'post',
            'title'   => $post->title,
            'slug'    => $post->slug,
            'status'  => $post->status === 'published' ? 'active' : 'inactive',
        ]);

        auth()->user()->tokens()->where('name', 'puck-builder')->delete();

        $token = auth()->user()->createToken(
            'puck-builder',
            ['*'],
            now()->addMinutes(config('app.puck_session', 5))
        )->plainTextToken;

        $url = config('cms.editor_base_url') . "/puck/edit/post/{$page->id}#token={$token}";
        $this->js('window.open(' . json_encode($url) . ', \'_blank\')');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'post-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $post = Post::findOrFail($this->deletingId);
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
                ->with(['category'])
                ->when($this->search, fn ($q) => $q
                    ->where('title->en', 'like', "%{$this->search}%")
                    ->orWhere('title->bn', 'like', "%{$this->search}%")
                    ->orWhere('slug', 'like', "%{$this->search}%"))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate(20),
        ])->layout('layouts.admin', ['title' => 'Posts']);
    }
}
