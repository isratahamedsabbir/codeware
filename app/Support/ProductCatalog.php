<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * The filter/facet language shared by the storefront shop page and the public
 * product API, so both surfaces agree on how to read `attributes[...]`,
 * `min_price`, `max_price` and `type` query params — and both surface the same
 * attribute facets in the sidebar / `meta`.
 */
class ProductCatalog
{
    /**
     * Applies the storefront filters to a product query, in a stable order:
     * selected attributes (JSON-contains match against the variations array),
     * price range against the base `price` column, then the product type.
     *
     * @param  array{
     *     attributes?: array<string, string>,
     *     min_price?: mixed,
     *     max_price?: mixed,
     *     type?: string,
     * }  $filters
     */
    public static function applyFilters(Builder $query, array $filters = []): Builder
    {
        foreach ((array) ($filters['attributes'] ?? []) as $name => $value) {
            if (is_string($value) && $value !== '' && is_string($name) && $name !== '') {
                $query->whereJsonContains('variations', ['attributes' => [$name => $value]]);
            }
        }

        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        if (isset($filters['type']) && in_array($filters['type'], ['physical', 'digital'], true)) {
            $query->where('product_type', $filters['type']);
        }

        return $query;
    }

    /**
     * Aggregates every distinct attribute → value a customer could filter on
     * out of all active products' visible combinations. A value counts once
     * per product even when several of its combinations share it. Shape:
     * ['Color' => ['Red' => 2, 'Blue' => 1], ...] — used by the shop sidebar
     * and the public API's `meta.attributes`.
     *
     * @return array<string, array<string, int>>
     */
    public static function attributeFacets(): array
    {
        return ContentCache::remember('shop-attribute-facets', function () {
            $facets = [];

            Product::active()->get(['id', 'variations'])->each(function (Product $product) use (&$facets) {
                $valuesByAttribute = [];

                foreach ($product->visibleVariations() as $row) {
                    foreach ((array) ($row['attributes'] ?? []) as $name => $value) {
                        if (is_string($name) && $name !== '' && is_string($value)) {
                            $valuesByAttribute[$name][$value] = true;
                        }
                    }
                }

                foreach ($valuesByAttribute as $name => $values) {
                    foreach (array_keys($values) as $value) {
                        $facets[$name][$value] = ($facets[$name][$value] ?? 0) + 1;
                    }
                }
            });

            return $facets;
        });
    }
}
