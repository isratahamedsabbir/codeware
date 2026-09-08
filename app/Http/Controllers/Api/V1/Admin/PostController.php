<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use App\Support\PageCascade;
use App\Support\Slug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $posts = Post::withTrashed()
            ->with(['category.page', 'user:id,name', 'tags', 'page'])
            ->orderByDesc('updated_at')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->paginate($perPage);

        return response()->json([
            'data' => $posts->map(fn ($post) => [
                'id' => $post->id,
                'slug' => $post->slug,
                'title' => $post->title,
                'status' => $post->status,
                'featured_image' => $post->featured_image,
                'reading_time' => $post->reading_time,
                'published_at' => $post->published_at?->toIso8601String(),
                'published_at_display' => $post->published_at?->toDisplay(),
                'deleted_at' => $post->deleted_at?->toIso8601String(),
                'deleted_at_display' => $post->deleted_at?->toDisplay(),
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
        $post = Post::withTrashed()->with(['category.page', 'user:id,name', 'tags', 'page'])->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $post->id,
                'slug' => $post->slug,
                'title' => $post->title,
                'content' => $post->content,
                'status' => $post->status,
                'featured_image' => $post->featured_image,
                'reading_time' => $post->reading_time,
                'published_at' => $post->published_at?->toIso8601String(),
                'published_at_display' => $post->published_at?->toDisplay(),
                'og_image' => $post->page?->og_image,
                'seo_title' => $post->page?->seo_title,
                'seo_description' => $post->page?->seo_description,
                'puck_data' => $post->page?->puck_data,
                'deleted_at' => $post->deleted_at?->toIso8601String(),
                'deleted_at_display' => $post->deleted_at?->toDisplay(),
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
            'slug' => ['nullable', 'string', ...Slug::uniqueRules(null)],
            'status' => 'sometimes|in:active,inactive',
            'puck_data' => 'nullable|array',
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'inactive';

        // The puck-builder content lives on the paired Page, not on the post itself.
        $puckData = $validated['puck_data'] ?? null;
        unset($validated['puck_data']);

        // The slug lives on the paired Page only — the post has no column for it.
        $slug = $validated['slug'] ?? null;
        unset($validated['slug']);

        $post = Post::create($validated)->refresh();
        $post->tags()->sync($validated['tag_ids'] ?? []);

        // Keep the paired Page in sync from creation on, same as the Livewire
        // admin form does — otherwise a page created later has a stale slug.
        Page::updateOrCreate(
            ['type' => 'post', 'post_id' => $post->id],
            array_filter([
                'user_id' => $request->user()->id,
                'title' => $post->getTranslations('title'),
                'slug' => $slug,
                'status' => $post->status,
                'puck_data' => $puckData,
            ])
        );

        return response()->json(['data' => ['id' => $post->id, 'slug' => $post->slug]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = Post::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|array',
            'title.en' => 'required_with:title|string|max:255',
            'title.bn' => 'nullable|string|max:255',
            'content' => 'sometimes|array',
            'content.en' => 'nullable|string',
            'content.bn' => 'nullable|string',
            'og_image' => 'sometimes|nullable|string',
            'seo_title' => 'sometimes|nullable|string|max:255',
            'seo_description' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'featured_image' => 'sometimes|nullable|string',
            'puck_data' => 'sometimes|nullable|array',
            'category_id' => 'sometimes|nullable|exists:categories,id,type,post',
            'slug' => ['sometimes', 'string', ...Slug::uniqueRules($post->page?->id)],
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        // SEO fields, OG image, and the puck-builder content all live on the paired
        // Page, not on the post itself.
        $pageFields = collect($validated)->only(['og_image', 'seo_title', 'seo_description', 'puck_data'])->all();
        unset($validated['og_image'], $validated['seo_title'], $validated['seo_description'], $validated['puck_data']);

        // The slug lives on the paired Page only — the post has no column for it.
        $slug = $validated['slug'] ?? null;
        unset($validated['slug']);

        $post->update($validated);
        $post->tags()->sync($validated['tag_ids'] ?? []);

        // Always keep the paired Page in sync (slug especially) regardless of
        // whether this request touched any SEO fields — a Livewire admin edit
        // syncs unconditionally too, so the two paths can't drift apart.
        // $post->page may already be cached (from the ?->id lookup above for
        // the uniqueness rule) — read the slug off this fresh return value
        // instead of $post->slug, which would resolve the stale cached page.
        $page = Page::updateOrCreate(
            ['type' => 'post', 'post_id' => $post->id],
            array_filter([
                'user_id' => $request->user()?->id,
                'title' => $post->getTranslations('title'),
                'slug' => $slug,
                'status' => $post->status,
                ...$pageFields,
            ])
        );

        return response()->json(['data' => ['id' => $post->id, 'slug' => $page->slug]]);
    }

    public function destroy(int $id): Response
    {
        $post = Post::with('page')->findOrFail($id);
        PageCascade::deletePageFor($post);
        $post->delete();

        return response()->noContent();
    }
}
