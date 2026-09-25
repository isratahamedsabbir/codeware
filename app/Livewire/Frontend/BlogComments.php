<?php

namespace App\Livewire\Frontend;

use App\Models\Comment;
use App\Models\Post;
use App\Support\Features;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The blog post's Comments section: approved top-level comments with their
 * approved replies, plus a form for any signed-in user to add a comment, and a
 * per-comment reply form (one level deep, matching the API). New comments and
 * replies start `pending` and appear once approved in Admin → Comments — the
 * same moderation flow as product reviews.
 */
class BlogComments extends Component
{
    #[Locked]
    public int $postId;

    public string $body = '';

    /** Which top-level comment the reply form is currently open under. */
    public ?int $replyToId = null;

    public string $replyBody = '';

    /** Inline confirmation after posting (awaiting approval). */
    public ?string $statusMessage = null;

    public function mount(int $postId): void
    {
        $this->postId = $postId;
    }

    #[Computed]
    public function post(): Post
    {
        return Post::published()->findOrFail($this->postId);
    }

    #[Computed]
    public function comments(): Collection
    {
        return $this->post->approvedComments()
            ->with(['user', 'replies' => fn ($q) => $q->approved()->with('user')])
            ->get();
    }

    public function beginReply(int $commentId): void
    {
        $this->replyToId = $commentId;
        $this->replyBody = '';
        $this->reset('statusMessage');
    }

    public function cancelReply(): void
    {
        $this->reset('replyToId', 'replyBody');
    }

    public function submit(): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(Features::enabled('comments'), 403);

        $this->validate(['body' => ['required', 'string', 'min:2', 'max:2000']]);

        $this->post->comments()->create([
            'user_id' => auth()->id(),
            'body' => trim($this->body),
            'status' => 'pending',
        ]);

        $this->reset('body');
        $this->statusMessage = __('Thanks! Your comment is awaiting approval.');
    }

    public function submitReply(int $commentId): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(Features::enabled('comments'), 403);

        $parent = Comment::query()
            ->whereKey($commentId)
            ->where('commentable_type', Post::class)
            ->where('commentable_id', $this->postId)
            ->whereNull('parent_id')
            ->first();

        abort_unless($parent, 404);

        $this->validate(['replyBody' => ['required', 'string', 'min:2', 'max:2000']]);

        $parent->replies()->create([
            'commentable_type' => Post::class,
            'commentable_id' => $this->postId,
            'user_id' => auth()->id(),
            'body' => trim($this->replyBody),
            'status' => 'pending',
        ]);

        $this->reset('replyToId', 'replyBody');
        $this->statusMessage = __('Thanks! Your reply is awaiting approval.');
    }

    public function render()
    {
        return view('livewire.frontend.blog-comments', [
            'comments' => $this->comments,
        ]);
    }
}
