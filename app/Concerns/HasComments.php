<?php

namespace App\Concerns;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Shared by every commentable model (Post, Product, Service) — all backed by
 * the single polymorphic `comments` table (commentable_type/commentable_id),
 * mirroring HasFaqs rather than a per-model comments table.
 */
trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Top-level, approved comments only — what the public API actually
     * shows. Replies are loaded separately per comment (see
     * Comment::replies()), each already scoped to its own approved-only
     * listing in CommentController.
     */
    public function approvedComments(): MorphMany
    {
        return $this->comments()->approved()->topLevel()->latest();
    }
}
