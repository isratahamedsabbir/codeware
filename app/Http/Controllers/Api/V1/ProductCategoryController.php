<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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

        $categories = ProductCategory::orderBy('sort_order')->get();

        return response()->json([
            'data' => $categories->map(fn ($cat) => [
                'id'          => $cat->id,
                'name'        => $cat->getTranslation('name', $locale, useFallbackLocale: true),
                'slug'        => $cat->slug,
                'description' => $cat->getTranslation('description', $locale, useFallbackLocale: true),
                'icon'        => $cat->icon,
                'sort_order'  => $cat->sort_order,
            ]),
        ]);
    }
}
