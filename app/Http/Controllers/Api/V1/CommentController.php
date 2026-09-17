<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    /**
     * Friendly type name (what clients send) → model class. Never trust a
     * client-supplied class name directly for a morph relation — this is the
     * only server-side source of truth for which classes are commentable.
     */
    private const COMMENTABLE_TYPES = [
        'post' => Post::class,
        'product' => Product::class,
        'service' => Service::class,
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => ['required', Rule::in(array_keys(self::COMMENTABLE_TYPES))],
            'commentable_id' => 'required|integer',
        ]);

        $perPage = max(1, min((int) $request->query('per_page', 15), 50));

        $comments = Comment::query()
            ->where('commentable_type', self::COMMENTABLE_TYPES[$validated['commentable_type']])
            ->where('commentable_id', $validated['commentable_id'])
            ->approved()
            ->topLevel()
            ->with(['user', 'replies' => fn ($q) => $q->approved()->with('user')])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $comments->map(fn (Comment $c) => $this->formatComment($c)),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => ['required', Rule::in(array_keys(self::COMMENTABLE_TYPES))],
            'commentable_id' => 'required|integer',
            'body' => 'required|string|max:2000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $modelClass = self::COMMENTABLE_TYPES[$validated['commentable_type']];
        $commentable = $this->resolveCommentable($modelClass, (int) $validated['commentable_id']);

        if (! $commentable) {
            throw ValidationException::withMessages(['commentable_id' => 'This item was not found.']);
        }

        if (! empty($validated['parent_id'])) {
            $parent = Comment::find($validated['parent_id']);

            // Replies are one level deep only — replying to a reply, or to a
            // comment on a different item, is rejected rather than silently
            // re-parented.
            if (! $parent
                || $parent->parent_id !== null
                || $parent->commentable_type !== $modelClass
                || (int) $parent->commentable_id !== (int) $validated['commentable_id']) {
                throw ValidationException::withMessages(['parent_id' => 'This comment cannot be replied to.']);
            }
        }

        $comment = Comment::create([
            'commentable_type' => $modelClass,
            'commentable_id' => $commentable->id,
            'user_id' => $request->user()->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => $this->formatComment($comment->load('user')),
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $comment = Comment::where('user_id', $request->user()->id)->findOrFail($id);
        $comment->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    private function resolveCommentable(string $modelClass, int $id): ?Model
    {
        return match ($modelClass) {
            Post::class => Post::published()->find($id),
            Product::class => Product::active()->find($id),
            Service::class => Service::active()->find($id),
            default => null,
        };
    }

    private function formatComment(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'user' => $comment->user ? [
                'id' => $comment->user->id,
                'name' => $comment->user->name,
            ] : null,
            'created_at' => $comment->created_at?->toIso8601String(),
            'created_at_display' => $comment->created_at?->toDisplay(),
            'replies' => $comment->relationLoaded('replies')
                ? $comment->replies->map(fn (Comment $r) => $this->formatComment($r))->values()
                : [],
        ];
    }
}
