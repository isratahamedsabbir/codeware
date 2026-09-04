<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');

        return in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
    }

    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $pages = Page::orderBy('sort_order')
            ->get()
            ->map(fn ($page) => $this->formatPage($page, $locale));

        return response()->json(['data' => $pages]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $page = Page::where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => $this->formatPage($page, $locale, withContent: true),
        ]);
    }

    private function formatPage(Page $page, string $locale, bool $withContent = false): array
    {
        $data = [
            'id' => $page->id,
            'slug' => $page->slug,
            'title' => $page->getTranslation('title', $locale, useFallbackLocale: true),
            // 'template' => $page->template,
            // 'sort_order' => $page->sort_order,
            'og_image' => $page->og_image,
            'seo_title' => $page->seo_title,
            'seo_description' => $page->seo_description,
            'og_title' => $page->og_title,
            'og_description' => $page->og_description,
            'no_index' => $page->no_index,
            'no_follow' => $page->no_follow,
            'puck_data' => $page->puck_data,
        ];

        if ($withContent) {
            $data['content'] = $page->getTranslation('content', $locale, useFallbackLocale: true);
            $data['constant'] = $page->constantMap();
        }

        return $data;
    }
}
