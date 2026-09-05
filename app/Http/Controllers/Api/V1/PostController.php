<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');

        return in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
    }

    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $perPage = min((int) $request->query('per_page', Setting::perPage()), 100);

        $posts = Post::published()
            ->with(['category', 'user:id,name', 'tags', 'page'])
            ->orderByDesc('published_at')
            ->when($request->query('category'), fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($request->query('search'), fn ($q, $search) => $q->where("title->{$locale}", 'like', "%{$search}%"))
            ->paginate($perPage);

        return response()->json([
            'data' => $posts->map(fn ($post) => $this->formatPost($post, $locale)),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $post = Post::published()
            ->with(['category', 'user:id,name', 'tags', 'page'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => $this->formatPost($post, $locale, withContent: true),
        ]);
    }

    private function formatPost(Post $post, string $locale, bool $withContent = false): array
    {
        $data = [
            'id' => $post->id,
            'slug' => $post->slug,
            'title' => $post->getTranslation('title', $locale, useFallbackLocale: true),
            'description' => $post->getTranslation('description', $locale, useFallbackLocale: true),
            'featured_image' => $post->featured_image,
            // 'reading_time' => $post->reading_time,
            // 'published_at' => $post->published_at?->toIso8601String(),
            // 'author' => $post->user ? ['id' => $post->user->id, 'name' => $post->user->name] : null,
            'category' => $post->category ? [
                'id' => $post->category->id,
                'slug' => $post->category->slug,
                'name' => $post->category->getTranslation('name', $locale, useFallbackLocale: true),
            ] : null,
            'tags' => $post->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'slug' => $tag->slug,
                'name' => $tag->getTranslation('name', $locale, useFallbackLocale: true),
            ])->values(),
            'page' => $this->formatPage($post->page),
        ];

        if ($withContent) {
            $data['content'] = $post->getTranslation('content', $locale, useFallbackLocale: true);
        }

        return $data;
    }

    private function formatPage(?Page $page): ?array
    {
        if (! $page) {
            return null;
        }

        return [
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
            'og_title' => $page->og_title,
            'og_description' => $page->og_description,
            'og_image' => $page->og_image,
            'twitter_title' => $page->twitter_title,
            'twitter_description' => $page->twitter_description,
            'twitter_image' => $page->twitter_image,
            'no_index' => $page->no_index,
            'no_follow' => $page->no_follow,
            'puck_data' => $page->puck_data,
        ];
    }
}
