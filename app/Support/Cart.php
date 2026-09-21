<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Storefront shopping cart.
 *
 * Session-scoped like App\Support\Favorites: a simple map kept in the session,
 * so guests and signed-in customers alike get a cart without any extra table.
 * Cart state is deliberately light — the cart holds only *what* to buy; every
 * price is recomputed server-side from the product at order time (see
 * App\Services\OrderPlacement), so the session can never be used to tamper
 * with totals.
 *
 * A line is keyed by the product id when it's the plain base product, or by a
 * variant signature ("{productId}::{Attribute=Value}::{...}") when the shopper
 * picked a specific option combination. Variant selection flows all the way to
 * the order item, which snapshots the chosen attributes + their price.
 */
class Cart
{
    public const SESSION_KEY = 'cart';

    public const MAX_QUANTITY = 1000;

    /**
     * A deterministic signature for an attribute map (name => value) so the
     * same combination always maps to the same cart line, regardless of the
     * order the client sent the keys.
     *
     * @param  array<string, string>  $attributes
     */
    public static function signature(array $attributes): string
    {
        ksort($attributes);

        return collect($attributes)
            ->map(fn (string $value, string $name) => $name.'='.$value)
            ->implode('::');
    }

    /**
     * The key for a cart line — a plain int product id for the base product,
     * or "{productId}::{signature}" when options were selected.
     *
     * @param  array<string, string>  $attributes
     */
    public static function lineKey(int $productId, array $attributes = []): int|string
    {
        if ($attributes === []) {
            return $productId;
        }

        return $productId.'::'.self::signature($attributes);
    }

    /**
     * Splits any line key back into its product id and attribute map.
     *
     * @return array{0: int, 1: array<string, string>}
     */
    public static function parseLineKey(int|string $key): array
    {
        $key = (string) $key;

        if (! str_contains($key, '::')) {
            return [(int) $key, []];
        }

        [$productId, $signature] = explode('::', $key, 2);

        $attributes = [];
        foreach (explode('::', $signature) as $pair) {
            [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');

            if ($name !== '') {
                $attributes[$name] = $value;
            }
        }

        return [(int) $productId, $attributes];
    }

    /**
     * Raw session contents — line key => quantity. Includes items whose
     * product may since have gone inactive/upcoming or whose variant combo was
     * removed; the orderable view is lines().
     *
     * @return array<int|string, int>
     */
    public static function items(): array
    {
        $items = (array) session(self::SESSION_KEY, []);

        foreach ($items as $key => $quantity) {
            if ($quantity <= 0) {
                unset($items[$key]);
            }
        }

        return $items;
    }

    public static function isEmpty(): bool
    {
        return empty(self::items());
    }

    /**
     * Total number of items across the whole cart (quantity-aware).
     */
    public static function count(): int
    {
        return array_sum(self::items());
    }

    public static function quantity(int|string $key): int
    {
        return (int) (self::items()[$key] ?? 0);
    }

    public static function has(int|string $key): bool
    {
        return array_key_exists($key, self::items());
    }

    /**
     * @param  array<string, string>  $attributes
     */
    public static function add(int $productId, int $quantity = 1, array $attributes = []): int|string
    {
        $key = self::lineKey($productId, $attributes);
        $items = self::items();
        $items[$key] = min(self::MAX_QUANTITY, (int) ($items[$key] ?? 0) + max(1, $quantity));

        session([self::SESSION_KEY => $items]);

        return $key;
    }

    public static function setQuantity(int|string $key, int $quantity): void
    {
        $items = self::items();

        if ($quantity <= 0) {
            unset($items[$key]);
        } else {
            $items[$key] = min(self::MAX_QUANTITY, $quantity);
        }

        session([self::SESSION_KEY => $items]);
    }

    public static function remove(int|string $key): void
    {
        $items = self::items();
        unset($items[$key]);

        session([self::SESSION_KEY => $items]);
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * A query over the products currently in the cart. Only active (non-draft,
     * non-upcoming) products resolve — anything that has since been made
     * inactive simply drops out of the orderable cart.
     */
    public static function products(): Builder
    {
        $ids = collect(array_keys(self::items()))
            ->map(fn (int|string $key) => self::parseLineKey($key)[0])
            ->unique()
            ->all();

        if (empty($ids)) {
            return Product::query()->whereRaw('1 = 0');
        }

        return Product::active()
            ->where('is_upcoming', false)
            ->whereIn('id', $ids);
    }

    /**
     * Resolved cart lines for display and order building — plain arrays, each
     * referencing the loaded product model plus the session's quantity,
     * selected options (if any) and server-computed money figures. A variant
     * line whose combination has since vanished from the catalog drops out,
     * exactly like a product that went inactive.
     *
     * @return Collection<int, array{
     *     key: int|string,
     *     product_id: int,
     *     product: Product,
     *     attributes: array<string, string>,
     *     quantity: int,
     *     unit_price: float,
     *     discount_price: ?float,
     *     line_total: float,
     *     in_stock: bool,
     * }>
     */
    public static function lines(): Collection
    {
        $products = self::products()->with(['page', 'brand'])->get()->keyBy('id');

        return collect(self::items())
            ->map(function (int $quantity, int|string $key) use ($products) {
                [$productId, $attributes] = self::parseLineKey($key);
                $product = $products->get($productId);

                if (! $product) {
                    return null;
                }

                $isVariant = $attributes !== [];
                $variation = $isVariant ? $product->variationRow($attributes) : null;

                // The chosen option no longer exists (hidden/removed by the
                // admin) — drop the line rather than silently order the base.
                if ($isVariant && $variation === null) {
                    return null;
                }

                $unitPrice = $isVariant ? $product->variationPrice($attributes) : (float) $product->price;
                $discountPrice = $isVariant ? $product->variationDiscount($attributes) : ($product->hasDiscount() ? (float) $product->discount_price : null);
                $sellPrice = $discountPrice ?? $unitPrice;

                return [
                    'key' => $key,
                    'product_id' => $product->id,
                    'product' => $product,
                    'attributes' => $attributes,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_price' => $discountPrice,
                    'line_total' => round($sellPrice * $quantity, 2),
                    'in_stock' => $isVariant ? $product->variationInStock($attributes) : $product->inStock(),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * A human-readable "Color: Red · Size: M" label for a line's selected
     * options — used by the cart page, checkout summary and order items.
     *
     * @param  array<string, string>  $attributes
     */
    public static function optionsLabel(array $attributes): string
    {
        return collect($attributes)
            ->map(fn (string $value, string $name) => "{$name}: {$value}")
            ->implode(' · ');
    }

    public static function subtotal(): float
    {
        return round(self::lines()->sum('line_total'), 2);
    }
}
