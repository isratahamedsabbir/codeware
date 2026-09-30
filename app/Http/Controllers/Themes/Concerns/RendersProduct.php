<?php

namespace App\Http\Controllers\Themes\Concerns;

use App\Models\Advertisement;
use App\Models\CmsSection;
use App\Models\Product;
use App\Models\Setting;
use App\Support\Favorites;
use App\Support\Features;

/**
 * A single product, and the two storefront pages that hang off one.
 *
 * Ecommerce-only for the same reason RendersCatalog is: a portfolio has no
 * product detail page, so it never mixes this trait in.
 */
trait RendersProduct
{
    /**
     * Product detail — the paired Page owns the slug, exactly like the public
     * API. Includes gallery, variations, brand/tags/categories, related
     * products, and the product page's CMS sections.
     */
    public function product(string $slug)
    {
        $product = Product::active()
            ->with(['categories.page', 'brand', 'tags', 'gallery', 'page', 'faqs'])
            ->whereHas('page', fn ($q) => $q->where('slug', $slug))
            ->first();

        abort_unless($product, 404, 'Unknown product.');

        // Manually linked related products (picked from the product form) win;
        // otherwise fall back to up to 4 same-category products — same rule as
        // the public API's ProductController::show().
        $related = $product->relatedProducts()
            ->active()
            ->with(['categories', 'brand', 'tags', 'page'])
            ->withSoldQuantity()
            ->limit(4)
            ->get();

        if ($related->isEmpty() && $product->categories->isNotEmpty()) {
            $related = Product::active()
                ->with(['categories', 'brand', 'tags', 'page'])
                ->withSoldQuantity()
                ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $product->categories->pluck('id')))
                ->where('id', '!=', $product->id)
                ->orderBy('sort_order')
                ->limit(4)
                ->get();
        }

        return $this->view('product', [
            'product' => $product,
            'related' => $related,
            'sections' => $product->page ? CmsSection::cachedForPage($product->page->id) : collect(),
            'page' => $product->page,
            'title' => $product->page?->seo_title ?: $product->name,
            'currentSlug' => $product->slug,
            'advertisement' => Features::enabled('advertisements') ? Advertisement::displayAd() : null,
        ]);
    }

    /**
     * Advertisement click count — bumps the counter, then sends the visitor to
     * the banner's destination URL (homepage when none is set). Never hits an
     * out-of-window or unknown banner.
     */
    public function adClick(string $code)
    {
        $advertisement = Advertisement::active()->where('code', $code)->firstOrFail();

        $advertisement->increment('clicks');

        return redirect()->away($advertisement->url ?: url('/'));
    }

    /**
     * The saved-favorites page — every product this visitor favorited (session
     * for guests, per-user rows once signed in, see App\Support\Favorites).
     */
    public function favorites()
    {
        return $this->view('favorites', [
            'products' => Favorites::products()
                ->with(['categories.page', 'brand', 'tags', 'page'])
                ->withSoldQuantity()
                ->orderBy('sort_order')
                ->paginate(Setting::perPage())
                ->withQueryString(),
            'title' => __('My Favorites'),
            'currentSlug' => 'favorites',
        ]);
    }
}
