<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Session-backed shopping cart API (see App\Support\Cart).
 *
 * No auth required — a guest's cart rides the session cookie the same way the
 * web storefront's does. The cart records product ids + quantities + a chosen
 * option combination (variants); prices are recomputed server-side at order
 * time, so the API never accepts or trusts a client amount. Ordering is
 * handled by the existing order endpoints.
 */
class CartController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->formatCart()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where('status', 'active')->where('is_upcoming', false),
            ],
            'quantity' => 'required|integer|min:1|max:1000',
            'attributes' => 'sometimes|array|max:10',
            'attributes.*' => 'required|string|max:190',
        ]);

        $attributes = array_map('strval', $validated['attributes'] ?? []);
        $product = Product::find($validated['product_id']);

        // A selected combo must still exist in the catalog — a line for a
        // vanished or hidden combination is never created.
        if ($attributes !== [] && $product->variationRow($attributes) === null) {
            throw ValidationException::withMessages([
                'attributes' => 'The selected combination is not available.',
            ]);
        }

        Cart::add(
            (int) $validated['product_id'],
            (int) $validated['quantity'],
            $attributes,
        );

        return response()->json(['data' => $this->formatCart()]);
    }

    public function update(Request $request, int $productId): JsonResponse
    {
        $attributes = array_map('strval', $request->input('attributes', []) ?? []);
        $key = Cart::lineKey($productId, $attributes);

        abort_unless(Cart::has($key), 404);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:0|max:1000',
            'attributes' => 'sometimes|array|max:10',
            'attributes.*' => 'required|string|max:190',
        ]);

        Cart::setQuantity($key, (int) $validated['quantity']);

        return response()->json(['data' => $this->formatCart()]);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $attributes = array_map('strval', $request->input('attributes', []) ?? []);
        $key = Cart::lineKey($productId, $attributes);

        abort_unless(Cart::has($key), 404);

        Cart::remove($key);

        return response()->json(['data' => $this->formatCart()]);
    }

    public function clear(): JsonResponse
    {
        Cart::clear();

        return response()->json(['data' => $this->formatCart()]);
    }

    /**
     * The orderable cart state — lines are filtered to products still active
     * and non-upcoming, and money is always the server-computed figure.
     */
    private function formatCart(): array
    {
        $lines = Cart::lines();

        return [
            'count' => $lines->sum('quantity'),
            'subtotal' => round($lines->sum('line_total'), 2),
            'items' => $lines->map(fn (array $line) => [
                'key' => $line['key'],
                'product_id' => $line['product_id'],
                'name' => $line['product']->name,
                'slug' => $line['product']->slug,
                'featured_image' => $line['product']->featured_image,
                'attributes' => $line['attributes'],
                'options_label' => Cart::optionsLabel($line['attributes']),
                'unit_price' => $line['unit_price'],
                'discount_price' => $line['discount_price'],
                'quantity' => $line['quantity'],
                'line_total' => $line['line_total'],
            ])->all(),
        ];
    }
}
