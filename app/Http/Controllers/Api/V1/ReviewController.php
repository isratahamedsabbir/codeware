<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use App\Models\Review;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /**
     * Friendly type name (what clients send) → model class. Never trust a
     * client-supplied class name directly for a morph relation — this is the
     * only server-side source of truth for which classes are reviewable.
     */
    private const REVIEWABLE_TYPES = [
        'post' => Post::class,
        'product' => Product::class,
        'service' => Service::class,
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reviewable_type' => ['required', Rule::in(array_keys(self::REVIEWABLE_TYPES))],
            'reviewable_id' => 'required|integer',
        ]);

        $perPage = max(1, min((int) $request->query('per_page', 15), 50));

        $reviews = Review::query()
            ->where('reviewable_type', self::REVIEWABLE_TYPES[$validated['reviewable_type']])
            ->where('reviewable_id', $validated['reviewable_id'])
            ->approved()
            ->with('user')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $reviews->map(fn (Review $review) => $this->formatReview($review)),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reviewable_type' => ['required', Rule::in(array_keys(self::REVIEWABLE_TYPES))],
            'reviewable_id' => 'required|integer',
            'rating' => 'required|integer|between:1,5',
            'title' => 'nullable|string|max:255',
            'body' => 'required|string|max:5000',
        ]);

        $modelClass = self::REVIEWABLE_TYPES[$validated['reviewable_type']];
        $reviewable = $this->resolveReviewable($modelClass, (int) $validated['reviewable_id']);

        if (! $reviewable) {
            throw ValidationException::withMessages(['reviewable_id' => 'This item was not found.']);
        }

        $review = Review::create([
            'reviewable_type' => $modelClass,
            'reviewable_id' => $reviewable->id,
            'user_id' => $request->user()->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'body' => $validated['body'],
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => $this->formatReview($review->load('user')),
        ], 201);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $review = Review::where('user_id', $request->user()->id)->findOrFail($id);
        $review->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    private function resolveReviewable(string $modelClass, int $id): ?Model
    {
        return match ($modelClass) {
            Post::class => Post::published()->find($id),
            Product::class => Product::active()->find($id),
            Service::class => Service::active()->find($id),
            default => null,
        };
    }

    private function formatReview(Review $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'title' => $review->title,
            'body' => $review->body,
            'user' => $review->user ? [
                'id' => $review->user->id,
                'name' => $review->user->name,
            ] : null,
            'created_at' => $review->created_at?->toIso8601String(),
            'created_at_display' => $review->created_at?->toDisplay(),
        ];
    }
}
