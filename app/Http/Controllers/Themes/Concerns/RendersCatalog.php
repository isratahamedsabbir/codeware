<?php

namespace App\Http\Controllers\Themes\Concerns;

use App\Models\CmsSection;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Support\Locale;
use App\Support\ProductCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * The catalog: the shop listing and the three landing pages that filter it.
 *
 * This is an ecommerce concept, so the trait is used by that theme's
 * controllers only — a theme with no shop never mixes it in and never carries
 * the code. What it does *not* decide is the layout: the shop page's facets and
 * grid are the theme's shop.blade.php, and a theme that wanted them in a
 * different order would override shop() in its own folder.
 */
trait RendersCatalog
{
    /**
     * The storefront catalog — every active product, facet-filterable by
     * category/brand/tag, free-text search, and sort order, all via query
     * params so the facets are just plain links. Rendered by the active theme's
     * own shop template, and 404s on a theme that ships none.
     */
    public function shop(Request $request)
    {
        $query = $this->productQuery();

        if ($slug = $request->query('category')) {
            $query->whereHas('categories.page', fn ($q) => $q->where('slug', $slug));
        }

        if ($slug = $request->query('brand')) {
            $query->where('brand_id', $this->resolveBrand($slug)?->id ?? abort(404, 'Unknown brand.'));
        }

        if ($slug = $request->query('tag')) {
            $query->whereHas('tags', fn ($q) => $q->whereKey($this->resolveTag($slug)?->id ?? abort(404, 'Unknown tag.')));
        }

        if ($term = trim((string) $request->query('search', ''))) {
            $locale = Locale::current();
            $needle = '%'.mb_strtolower($term).'%';

            // json_unquote(json_extract(...)) returns a binary-collation string
            // in MySQL, making a plain LIKE case-sensitive even on a
            // case-insensitive column — so compare both sides lowercased.
            $query->where(function (Builder $q) use ($needle, $locale) {
                $q->whereRaw('LOWER(json_unquote(json_extract(`name`, \'$."en"\'))) LIKE ?', [$needle]);

                if ($locale !== 'en') {
                    $q->orWhereRaw('LOWER(json_unquote(json_extract(`name`, \'$."'.mb_strtolower($locale).'"\'))) LIKE ?', [$needle]);
                }
            });
        }

        ProductCatalog::applyFilters($query, $this->filtersFrom($request));

        switch ($request->query('sort')) {
            case 'price_asc':
                $query->orderBy('price');
                break;
            case 'price_desc':
                $query->orderByDesc('price');
                break;
            case 'newest':
                $query->latest();
                break;
        }

        [$priceMin, $priceMax] = $this->priceBounds();

        return $this->view('shop', [
            'products' => $query->paginate(Setting::perPage())->withQueryString(),
            'categories' => $this->shopCategories(),
            'brands' => $this->shopBrands(),
            'tags' => $this->shopTags(),
            'attributeFacets' => ProductCatalog::attributeFacets(),
            'priceBounds' => (object) ['min' => $priceMin, 'max' => $priceMax],
            'filters' => [
                'category' => (string) $request->query('category', ''),
                'brand' => (string) $request->query('brand', ''),
                'tag' => (string) $request->query('tag', ''),
                'search' => (string) $request->query('search', ''),
                'sort' => (string) $request->query('sort', ''),
                'attributes' => $this->filtersFrom($request)['attributes'],
                'min_price' => is_numeric($request->query('min_price')) ? $request->query('min_price') : '',
                'max_price' => is_numeric($request->query('max_price')) ? $request->query('max_price') : '',
                'type' => (string) $request->query('type', ''),
            ],
            'title' => __('Shop'),
            'currentSlug' => 'shop',
        ]);
    }

    /**
     * A product-category landing page — the category's own content/sections on
     * top, then a grid of the products directly in it. Category slugs, like
     * product slugs, live on the paired Page.
     */
    public function category(string $slug)
    {
        $category = ProductCategory::active()
            ->whereHas('page', fn ($q) => $q->where('slug', $slug))
            ->with(['page', 'parent'])
            ->first();

        abort_unless($category, 404, 'Unknown category.');

        $products = $this->productQuery()
            ->whereHas('categories', fn ($q) => $q->whereKey($category->id))
            ->paginate(Setting::perPage())
            ->withQueryString();

        $children = ProductCategory::active()
            ->where('parent_id', $category->id)
            ->with('page')
            ->orderBy('sort_order')
            ->get();

        return $this->view('category', [
            'category' => $category,
            'children' => $children,
            'products' => $products,
            'sections' => $category->page ? CmsSection::cachedForPage($category->page->id) : collect(),
            'page' => $category->page,
            'title' => $category->page?->seo_title ?: $category->name,
            'currentSlug' => $category->slug,
        ]);
    }

    /**
     * A brand landing page — brand logo/name then the brand's products. Brands
     * have no Page/slug of their own; the URL slug is derived from the primary
     * locale name (see resolveBrand()).
     */
    public function brand(string $slug)
    {
        $brand = $this->resolveBrand($slug);

        abort_unless($brand, 404, 'Unknown brand.');

        return $this->view('brand', [
            'brand' => $brand,
            'products' => $this->productQuery()
                ->where('brand_id', $brand->id)
                ->paginate(Setting::perPage())
                ->withQueryString(),
            'title' => $brand->name,
            'currentSlug' => $brand->slug,
        ]);
    }

    /**
     * A tag landing page — tag name then the tag's products. Tags, like
     * brands, have no Page/slug of their own; the URL slug is derived from the
     * primary locale name (see resolveTag()).
     */
    public function tag(string $slug)
    {
        $tag = $this->resolveTag($slug);

        abort_unless($tag, 404, 'Unknown tag.');

        return $this->view('tag', [
            'tag' => $tag,
            'products' => $this->productQuery()
                ->whereHas('tags', fn ($q) => $q->whereKey($tag->id))
                ->paginate(Setting::perPage())
                ->withQueryString(),
            'title' => $tag->name,
            'currentSlug' => $tag->slug,
        ]);
    }
}
