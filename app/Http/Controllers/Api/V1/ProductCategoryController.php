<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
            'data' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->getTranslation('name', $locale, useFallbackLocale: true),
                'slug' => $cat->slug,
                'description' => $cat->getTranslation('description', $locale, useFallbackLocale: true),
                'icon' => $cat->icon,
                'sort_order' => $cat->sort_order,
                'page' => $this->formatPage($cat->page),
            ]),
        ]);
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
            'no_index' => $page->no_index,
            'no_follow' => $page->no_follow,
            'puck_data' => $page->puck_data,
        ];
    }
}
