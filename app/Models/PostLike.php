<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single blog post like — one row per (post, user), enforced by a unique
 * index, so liking is a toggle (create/delete) rather than an increment.
 */
class PostLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id', 'user_id',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
