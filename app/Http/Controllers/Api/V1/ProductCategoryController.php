<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CmsSection;
use App\Models\Page;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');

        return in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
    }

    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $categories = ProductCategory::with('page')->orderBy('sort_order')->get();

        return response()->json([
            'data' => $categories->map(fn ($cat) => $this->formatCategory($cat, $locale)),
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $category = ProductCategory::with('page')
            ->whereHas('page', fn ($q) => $q->where('slug', $slug))
            ->firstOrFail();

        return response()->json([
            'data' => $this->formatCategory($category, $locale, withCms: true),
        ]);
    }

    private function formatCategory(ProductCategory $category, string $locale, bool $withCms = false): array
    {
        $data = [
            'id' => $category->id,
            'name' => $category->getTranslation('name', $locale, useFallbackLocale: true),
            'slug' => $category->slug,
            'icon' => $category->icon,
            'sort_order' => $category->sort_order,
            'page' => $this->formatPage($category->page),
        ];

        if ($withCms) {
            $data['cms'] = $this->formatCms($category->page);
        }

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatCms(?Page $page): array
    {
        if (! $page) {
            return [];
        }

        return CmsSection::cachedForPage($page->id)->map(fn (CmsSection $cms) => [
            'id' => $cms->id,
            'page_id' => $cms->page_id,
            'name' => $cms->name,
            'cards' => $cms->localizedCards(),
            'constant' => $cms->constantMap(),
        ])->values()->all();
    }

    private function formatPage(?Page $page): ?array
    {
        if (! $page) {
            return null;
        }

        return [
            'meta_data' => [
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
            ],
            'puck_data' => $page->puck_data,
            'constant' => $page->constantMap(),
        ];
    }
}
