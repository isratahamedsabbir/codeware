<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
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
        $perPage = min((int) $request->query('per_page', 15), 100);

        $posts = Post::published()
            ->with(['category', 'user:id,name'])
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
            ->with(['category', 'user:id,name'])
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
            //'reading_time' => $post->reading_time,
            //'published_at' => $post->published_at?->toIso8601String(),
            'seo_title' => $post->getTranslation('seo_title', $locale, useFallbackLocale: true),
            'seo_description' => $post->getTranslation('seo_description', $locale, useFallbackLocale: true),
            'og_image' => $post->og_image,
            //'author' => $post->user ? ['id' => $post->user->id, 'name' => $post->user->name] : null,
            'category' => $post->category ? [
                'id' => $post->category->id,
                'slug' => $post->category->slug,
                'name' => $post->category->getTranslation('name', $locale, useFallbackLocale: true),
            ] : null,
        ];

        if ($withContent) {
            $data['content']   = $post->getTranslation('content', $locale, useFallbackLocale: true);
            $data['puck_data'] = $post->page->puck_data;
        }

        return $data;
    }
}
