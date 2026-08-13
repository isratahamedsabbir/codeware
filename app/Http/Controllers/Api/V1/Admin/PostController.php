<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $posts = Post::withTrashed()
            ->with(['category:id,slug', 'user:id,name', 'tags'])
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
                'author' => $post->user ? ['id' => $post->user->id, 'name' => $post->user->name] : null,
                'tags' => $post->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                ])->values(),
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
        $post = Post::withTrashed()->with(['category', 'user:id,name', 'tags', 'page'])->findOrFail($id);

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
                'og_image' => $post->page?->og_image,
                'seo_title' => $post->page?->seo_title,
                'seo_description' => $post->page?->seo_description,
                'puck_data' => $post->puck_data,
                'deleted_at' => $post->deleted_at?->toIso8601String(),
                'category' => $post->category,
                'author' => $post->user ? ['id' => $post->user->id, 'name' => $post->user->name] : null,
                'tags' => $post->tags->map(fn ($tag) => [
                    'id' => $tag->id,
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                ])->values(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|array',
            'title.en' => 'required|string|max:255',
            'title.bn' => 'nullable|string|max:255',
            'slug' => 'nullable|string|unique:posts,slug',
            'status' => 'sometimes|in:active,inactive',
            'puck_data' => 'nullable|array',
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'inactive';

        $post = Post::create($validated);
        $post->tags()->sync($validated['tag_ids'] ?? []);

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
            'og_image' => 'sometimes|nullable|string',
            'seo_title' => 'sometimes|array',
            'seo_title.en' => 'nullable|string|max:255',
            'seo_title.bn' => 'nullable|string|max:255',
            'seo_description' => 'sometimes|array',
            'seo_description.en' => 'nullable|string',
            'seo_description.bn' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'featured_image' => 'sometimes|nullable|string',
            'puck_data' => 'sometimes|nullable|array',
            'category_id' => 'sometimes|nullable|exists:categories,id,type,post',
            'slug' => 'sometimes|string|unique:posts,slug,'.$post->id,
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        // SEO fields and OG image live on the paired Page, not on the post itself.
        $seo = collect($validated)->only(['og_image', 'seo_title', 'seo_description'])->all();
        unset($validated['og_image'], $validated['seo_title'], $validated['seo_description']);

        $post->update($validated);
        $post->tags()->sync($validated['tag_ids'] ?? []);

        if ($seo !== []) {
            Page::updateOrCreate(
                ['type' => 'post', 'post_id' => $post->id],
                array_filter([
                    'user_id' => $request->user()?->id,
                    'title' => $post->getTranslations('title'),
                    'slug' => $post->slug,
                    'status' => $post->status,
                    'description' => $post->getTranslations('description') ?: null,
                    ...$seo,
                ])
            );
        }

        return response()->json(['data' => ['id' => $post->id, 'slug' => $post->slug]]);
    }
}
