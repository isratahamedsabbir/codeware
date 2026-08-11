<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $posts = Post::withTrashed()
            ->with(['category:id,slug', 'tags:id,slug', 'user:id,name'])
            ->orderByDesc('updated_at')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->paginate($perPage);

        return response()->json([
            'data' => $posts->map(fn ($post) => [
                'id' => $post->id,
                'slug' => $post->slug,
                'title' => $post->title,
                'description' => $post->description,
                'status' => $post->status,
                'featured_image' => $post->featured_image,
                'reading_time' => $post->reading_time,
                'published_at' => $post->published_at?->toIso8601String(),
                'deleted_at' => $post->deleted_at?->toIso8601String(),
                'category' => $post->category,
                'tags' => $post->tags->values(),
                'author' => $post->user ? ['id' => $post->user->id, 'name' => $post->user->name] : null,
            ]),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $post = Post::withTrashed()->with(['category', 'tags', 'user:id,name'])->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $post->id,
                'slug' => $post->slug,
                'title' => $post->title,
                'description' => $post->description,
                'content' => $post->content,
                'status' => $post->status,
                'featured_image' => $post->featured_image,
                'reading_time' => $post->reading_time,
                'published_at' => $post->published_at?->toIso8601String(),
                'seo_title' => $post->seo_title,
                'seo_description' => $post->seo_description,
                'puck_data'       => $post->puck_data,
                'deleted_at' => $post->deleted_at?->toIso8601String(),
                'category' => $post->category,
                'tags' => $post->tags->values(),
                'author' => $post->user ? ['id' => $post->user->id, 'name' => $post->user->name] : null,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'     => 'required|array',
            'title.en'  => 'required|string|max:255',
            'title.bn'  => 'nullable|string|max:255',
            'slug'      => 'nullable|string|unique:posts,slug',
            'status'    => 'sometimes|in:active,inactive',
            'puck_data' => 'nullable|array',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status']  = $validated['status'] ?? 'inactive';

        $post = Post::create($validated);

        return response()->json(['data' => ['id' => $post->id, 'slug' => $post->slug]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = Post::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|array',
            'title.en' => 'required_with:title|string|max:255',
            'title.bn' => 'nullable|string|max:255',
            'description' => 'sometimes|array',
            'description.en' => 'nullable|string',
            'description.bn' => 'nullable|string',
            'content' => 'sometimes|array',
            'content.en' => 'nullable|string',
            'content.bn' => 'nullable|string',
            'seo_title' => 'sometimes|array',
            'seo_title.en' => 'nullable|string|max:255',
            'seo_title.bn' => 'nullable|string|max:255',
            'seo_description' => 'sometimes|array',
            'seo_description.en' => 'nullable|string',
            'seo_description.bn' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'featured_image' => 'sometimes|nullable|string',
            'puck_data' => 'sometimes|nullable|array',
            'category_id' => 'sometimes|nullable|exists:blog_categories,id',
            'slug' => 'sometimes|string|unique:posts,slug,' . $post->id,
        ]);

        $post->update($validated);

        return response()->json(['data' => ['id' => $post->id, 'slug' => $post->slug]]);
    }
}
