<?php

namespace App\Livewire\Frontend;

use App\Models\Post;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The blog post's reactions bar: the like/unlike heart (toggle) and the view
 * count. Liking needs a signed-in user — guests see the count with a sign-in
 * link instead. No moderation involved, unlike comments/reviews.
 */
class PostReactions extends Component
{
    #[Locked]
    public int $postId;

    public function mount(int $postId): void
    {
        $this->postId = $postId;
    }

    #[Computed]
    public function post(): Post
    {
        return Post::findOrFail($this->postId);
    }

    #[Computed]
    public function likedByMe(): bool
    {
        return auth()->check() && $this->post->likes()->where('user_id', auth()->id())->exists();
    }

    public function toggleLike(): void
    {
        abort_unless(auth()->check(), 403);

        $existing = $this->post->likes()->where('user_id', auth()->id())->first();

        if ($existing) {
            $existing->delete();
        } else {
            $this->post->likes()->create(['user_id' => auth()->id()]);
        }

        unset($this->likedByMe);
    }

    public function render()
    {
        return view('livewire.frontend.post-reactions', [
            'post' => $this->post,
            'likedByMe' => $this->likedByMe,
            'likesCount' => $this->post->likes()->count(),
        ]);
    }
}
