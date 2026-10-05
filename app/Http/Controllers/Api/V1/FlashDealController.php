<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FlashDeal;
use App\Models\Product;
use App\Support\Locale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public, read-only flash deals. Only deals that are live right now are ever
 * returned: switched on and between starts_at and ends_at. Prices are the ones
 * the cart will charge (see Product::flashPrice()), so a client can show them
 * as-is and count down to ends_at.
 */
class FlashDealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $deals = FlashDeal::live()
            ->with(['products' => fn ($q) => $q->active()->with(['page', 'flashDeals'])->orderBy('sort_order')])
            ->orderBy('ends_at')
            ->get()
            ->map(fn (FlashDeal $deal) => $this->formatDeal($deal, $locale))
            // A live deal whose products are all inactive/deleted has nothing to show.
            ->filter(fn (array $deal) => $deal['products'] !== [])
            ->values();

        return response()->json([
            'data' => $deals,
            'meta' => [
                // true = there is at least one live deal; clients can use it to
                // decide whether to show their "Flash Deals" entry point at all.
                'has_live_deals' => $deals->isNotEmpty(),
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $deal = FlashDeal::live()
            ->with(['products' => fn ($q) => $q->active()->with(['page', 'flashDeals'])->orderBy('sort_order')])
            ->findOrFail($id);

        return response()->json(['data' => $this->formatDeal($deal, $locale)]);
    }

    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');

        return is_string($locale) && Locale::isSupported($locale) ? $locale : Locale::default();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDeal(FlashDeal $deal, string $locale): array
    {
        return [
            'id' => $deal->id,
            'name' => $deal->name,
            'type' => $deal->type,
            'value' => (float) $deal->value,
            'starts_at' => $deal->starts_at->toIso8601String(),
            'ends_at' => $deal->ends_at->toIso8601String(),
            'seconds_left' => max(0, (int) now()->diffInSeconds($deal->ends_at, false)),
            'products' => $deal->products
                ->map(fn (Product $product) => $this->formatProduct($product, $locale))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatProduct(Product $product, string $locale): array
    {
        $price = (float) $product->price;
        $sale = $product->effectiveDiscount();

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->getTranslation('name', $locale, useFallbackLocale: true),
            'featured_image' => $product->featured_image,
            'price' => $price,
            'deal_price' => $sale,
            'discount_percent' => $sale !== null && $price > 0 ? (int) round(($price - $sale) / $price * 100) : null,
            'in_stock' => $product->inStock(),
        ];
    }
}
