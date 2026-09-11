<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CmsSection;
use App\Models\Page;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Locale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');

        return is_string($locale) && Locale::isSupported($locale) ? $locale : Locale::default();
    }

    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $perPage = max(1, min((int) $request->query('per_page', Setting::perPage()), 100));

        $products = Product::active()
            ->with(['categories.page', 'page'])
            ->orderBy('sort_order')
            ->when($request->query('category'), fn ($q, $slug) => $q->whereHas('categories.page', fn ($c) => $c->where('slug', $slug)))
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
            ->with(['categories.page', 'gallery', 'page'])
            ->whereHas('page', fn ($q) => $q->where('slug', $slug))
            ->firstOrFail();

        $categoryIds = $product->categories->pluck('id');

        $related = $categoryIds->isNotEmpty()
            ? Product::active()
                ->with(['categories', 'page'])
                ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds))
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
            'discount_price' => $product->hasDiscount() ? (float) $product->discount_price : null,
            'quantity' => $product->quantity,
            'in_stock' => $product->inStock(),
            'charge_shipping' => $product->charge_shipping,
            'featured_image' => $product->featured_image,
            'is_featured' => $product->is_featured,
            // 'sort_order'      => $product->sort_order,
            'categories' => $product->categories->map(fn ($category) => [
                'id' => $category->id,
                'slug' => $category->slug,
                'name' => $category->getTranslation('name', $locale, useFallbackLocale: true),
            ])->values(),
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
            // Stored flat (one row per attribute+value, see Admin\Products\Form)
            // but grouped here by attribute for the frontend, same shape as before.
            $data['variations'] = collect($product->variations ?? [])
                ->groupBy('attribute')
                ->map(fn ($rows, $attributeName) => [
                    'name' => $attributeName,
                    'values' => $rows->map(function ($row) {
                        $price = ($row['price'] ?? null) !== null ? (float) $row['price'] : null;
                        $discountPrice = ($row['discount_price'] ?? null) !== null ? (float) $row['discount_price'] : null;
                        $quantity = ($row['quantity'] ?? null) !== null ? (int) $row['quantity'] : null;

                        return [
                            'name' => $row['value'] ?? '',
                            'price' => $price,
                            // Only counts if it's actually cheaper than this value's own
                            // price — same guard as Product::hasDiscount().
                            'discount_price' => $discountPrice !== null && $price !== null && $discountPrice < $price
                                ? $discountPrice
                                : null,
                            'quantity' => $quantity,
                            // Null quantity means stock isn't tracked for this value —
                            // same convention as Product::inStock().
                            'in_stock' => $quantity === null || $quantity > 0,
                        ];
                    })->values(),
                ])
                ->values();
            $data['related_products'] = $related->map(fn ($p) => $this->formatProduct($p, $locale))->values();
            $data['cms'] = $this->formatCms($product->page);
        }

        return $data;
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
}
