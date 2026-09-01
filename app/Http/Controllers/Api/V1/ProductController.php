<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');

        return in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
    }

    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $perPage = max(1, min((int) $request->query('per_page', Setting::perPage()), 100));

        $products = Product::active()
            ->with(['category', 'page'])
            ->orderBy('sort_order')
            ->when($request->query('category'), fn ($q, $slug) => $q->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($request->query('search'), fn ($q, $search) => $q->where("name->{$locale}", 'like', "%{$search}%"))
            ->when($request->query('featured') === '1', fn ($q) => $q->where('is_featured', true))
            ->paginate($perPage);

        return response()->json([
            'data' => $products->map(fn ($p) => $this->formatProduct($p, $locale)),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $product = Product::active()
            ->with(['category', 'gallery', 'page'])
            ->where('slug', $slug)
            ->firstOrFail();

        $related = $product->product_category_id
            ? Product::active()
                ->with('category')
                ->where('product_category_id', $product->product_category_id)
                ->where('id', '!=', $product->id)
                ->orderBy('sort_order')
                ->limit(4)
                ->get()
            : collect();

        return response()->json([
            'data' => $this->formatProduct($product, $locale, withDetail: true, related: $related),
        ]);
    }

    private function formatProduct(Product $product, string $locale, bool $withDetail = false, $related = null): array
    {
        $data = [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->getTranslation('name', $locale, useFallbackLocale: true),
            'price' => (float) $product->price,
            'featured_image' => $product->featured_image,
            'is_featured' => $product->is_featured,
            // 'sort_order'      => $product->sort_order,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'slug' => $product->category->slug,
                'name' => $product->category->getTranslation('name', $locale, useFallbackLocale: true),
            ] : null,
            'page' => $this->formatPage($product->page),
        ];

        if ($withDetail) {
            $data['description'] = $product->getTranslation('description', $locale, useFallbackLocale: true);
            $data['faq'] = collect($product->faq ?? [])->map(fn ($item) => [
                'question' => $item['question'][$locale] ?? $item['question']['en'] ?? '',
                'answer' => $item['answer'][$locale] ?? $item['answer']['en'] ?? '',
            ])->values();
            $data['gallery'] = $product->gallery->map(fn ($m) => [
                'id' => $m->id,
                'url' => $m->url,
                'alt' => $m->alt_text ?? '',
                'sort_order' => $m->pivot->sort_order,
            ])->values();
            $data['related_products'] = $related->map(fn ($p) => $this->formatProduct($p, $locale))->values();
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
            'no_index' => $page->no_index,
            'no_follow' => $page->no_follow,
            'puck_data' => $page->puck_data,
        ];
    }
}
