<?php

namespace App\Livewire\Admin\Comments;

use App\Concerns\HasPerPage;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\Service;
use App\Support\AdminActivity;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use HasPerPage, WithPagination;

    /**
     * Friendly type name (shown in the filter/table) → model class — kept in
     * sync with CommentController::COMMENTABLE_TYPES.
     */
    private const TYPES = [
        'post' => Post::class,
        'product' => Product::class,
        'service' => Service::class,
    ];

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public ?int $viewingId = null;

    public ?int $deletingId = null;

    /** @var array<int, int> */
    public array $selectedIds = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updateStatus(int $id, string $status): void
    {
        $comment = Comment::findOrFail($id);
        $comment->update(['status' => $status]);

        AdminActivity::log('updated', "Comment #{$comment->id} marked {$status}");
        $this->dispatch('notify', message: 'Comment status updated');
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
        $this->dispatch('open-modal', name: 'comment-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $comment = Comment::findOrFail($this->deletingId);
            AdminActivity::log('deleted', "Comment #{$comment->id}");
            $comment->delete();
            $this->dispatch('notify', message: 'Comment deleted successfully');
            $this->deletingId = null;
        }
        $this->dispatch('close-modal', name: 'comment-delete');
    }

    /**
     * Ctrl/Cmd+click row selection or the row's own checkbox (see the view) —
     * toggles one comment id in/out of the bulk-selection.
     */
    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));

            return;
        }

        $this->selectedIds[] = $id;
    }

    public function confirmBulkDelete(): void
    {
        if ($this->selectedIds === []) {
            return;
        }

        $this->dispatch('open-modal', name: 'comment-bulk-delete');
    }

    public function bulkDelete(): void
    {
        $comments = Comment::whereIn('id', $this->selectedIds)->get();

        foreach ($comments as $comment) {
            AdminActivity::log('deleted', "Comment #{$comment->id}");
            $comment->delete();
        }

        $count = $comments->count();
        $this->selectedIds = [];

        $this->dispatch('notify', message: "{$count} ".Str::plural('comment', $count).' deleted successfully');
        $this->dispatch('close-modal', name: 'comment-bulk-delete');
    }

    /**
     * The friendly type label ('Post'/'Product'/'Service') for a comment row
     * — the FQCN is what's actually stored in commentable_type.
     */
    public function typeLabel(string $commentableType): string
    {
        $friendly = array_search($commentableType, self::TYPES, true);

        return $friendly ? ucfirst($friendly) : 'Unknown';
    }

    public function render()
    {
        return view('livewire.admin.comments.index', [
            'comments' => Comment::query()
                ->with(['user', 'commentable', 'parent'])
                ->withCount('replies')
                ->when($this->search, fn ($q) => $q->where('body', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn ($q2) => $q2->where('name', 'like', "%{$this->search}%")))
                ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->typeFilter && isset(self::TYPES[$this->typeFilter]), fn ($q) => $q->where('commentable_type', self::TYPES[$this->typeFilter]))
                ->topLevel()
                ->latest()
                ->paginate($this->perPage),
        ])->layout('layouts.admin', ['title' => 'Comments', 'hidePageHeading' => true]);
    }
}
