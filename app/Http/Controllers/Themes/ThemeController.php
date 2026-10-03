<?php

namespace App\Http\Controllers\Themes;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\Type;
use App\Support\ContentCache;
use App\Support\Frontend;
use App\Support\Locale;
use App\Support\Themes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * What every themed storefront controller shares, whichever theme it belongs to.
 *
 * The themed controllers used to be two application-wide classes —
 * FrontendController and CustomerController — so a theme's pages were spread
 * across files named after the application rather than the theme, and a theme
 * that shipped a different shop listing or a different account layout had
 * nowhere to put that. They are now one class per theme per concern, under
 * Themes/{slug}/, matching the other per-theme halves: themes/{slug}/routes/web.php,
 * themes/{slug}/public/ and themes/{slug}/.
 *
 * What deliberately stays here is everything that is not a theme's decision:
 * the payload every page's header/footer needs, the product query the catalog
 * pages share, and the slug derivations for brands and tags. Those are the same
 * query on every theme and belong in one place — a theme overrides a page by
 * overriding the method, in its own folder, not by copying the base.
 *
 * Not abstract in the type sense despite being the parent of every themed
 * controller: it is instantiated nowhere, but a controller with no routes of
 * its own is a base class, and leaving it concrete keeps `new` legal for the
 * trait-level unit tests.
 */
class ThemeController extends Controller
{
    /**
     * The view a themed page renders through — the active theme's own template,
     * or a 404 when it ships none (see Themes::viewOrFail()).
     *
     * Every themed page goes through here rather than reaching for
     * Themes::active() and building a view name itself, so a theme missing a
     * template degrades to "not found" instead of a 500 from an unresolvable
     * view.
     */
    protected function view(string $template, array $data = [])
    {
        return view(Themes::viewOrFail($template), $this->viewData($data));
    }

    /**
     * The payload every themed page's header/footer partials need, merged under
     * whatever the page set itself.
     *
     * A page's own keys win, which is what lets a page override the default
     * title or pass its own `page`/`sections` without this having to know which
     * pages do.
     */
    protected function viewData(array $data = []): array
    {
        return $data + [
            'title' => Setting::translated('seo_meta_title') ?: Setting::get('site_name'),
            'page' => null,
            'sections' => collect(),
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
        ];
    }

    /**
     * The base query for every product browse page: only active products,
     * with everything a card or detail view needs eager-loaded, ordered by
     * sort_order like the admin and public API.
     */
    protected function productQuery(): Builder
    {
        return Product::active()
            ->with(['categories.page', 'brand', 'tags', 'page'])
            ->withSoldQuantity()
            ->orderBy('sort_order');
    }

    /**
     * Active top-level categories (with per-child subtrees attached) and the
     * count of active products in each — used by the shop sidebar. The
     * depth-first flattening on ProductCategory::tree() drives the facet list.
     */
    protected function shopCategories(): Collection
    {
        $count = fn (Builder $q) => $q->where('products.status', 'active');

        return ProductCategory::active()
            ->with('page')
            ->withCount(['products' => fn ($q) => $q->active()])
            ->with(['children' => function (HasMany $q) use ($count) {
                $q->active()->with('page')
                    ->withCount(['products' => $count]);
            }])
            ->orderBy('sort_order')
            ->get();
    }

    protected function shopBrands(): Collection
    {
        // A brand belongs to exactly one type now, so the shop facets are the
        // product pool only — a post-pool brand would otherwise show up here as
        // a filter that leads to an empty product list.
        return ProductBrand::whereIn('type_id', Type::subquery(Type::PRODUCT))
            ->active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->get();
    }

    protected function shopTags(): Collection
    {
        return Tag::whereIn('type_id', Type::subquery(Type::PRODUCT))
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Finds a brand by its URL slug — the slug is computed from the primary
     * locale name (same separators as Slug::make), since brands have no Page.
     */
    protected function resolveBrand(string $slug): ?ProductBrand
    {
        return ProductBrand::whereIn('type_id', Type::subquery(Type::PRODUCT))->active()->get()
            ->first(fn (ProductBrand $brand) => $this->taxonomyKey($brand) === Str::slug($slug, '-'));
    }

    /**
     * Finds a tag by its URL slug — same derivation as resolveBrand().
     */
    protected function resolveTag(string $slug): ?Tag
    {
        return Tag::whereIn('type_id', Type::subquery(Type::PRODUCT))
            ->where('status', 'active')
            ->get()
            ->first(fn (Tag $tag) => $this->taxonomyKey($tag) === Str::slug($slug, '-'));
    }

    protected function taxonomyKey(Model $item): string
    {
        $name = (string) ($item->getTranslation('name', Locale::primary(), false) ?: $item->getTranslation('name', 'en', false));

        return Str::slug(is_array($name) ? reset($name) : $name, '-');
    }

    /**
     * The storefront filter set from the request — attributes are read as
     * `attributes[Color]=Red` while price and type use their flat query
     * params. Mirrored by the public API's ProductController.
     *
     * @return array{attributes: array<string, string>, min_price: mixed, max_price: mixed, type: string}
     */
    protected function filtersFrom(Request $request): array
    {
        $attributes = array_filter(
            (array) $request->query('attributes', []),
            fn ($value, $name) => is_string($name) && $name !== '' && is_string($value) && $value !== '',
            ARRAY_FILTER_USE_BOTH,
        );

        return [
            'attributes' => $attributes,
            'min_price' => $request->query('min_price'),
            'max_price' => $request->query('max_price'),
            'type' => (string) $request->query('type', ''),
        ];
    }

    /**
     * Counts a post view, but only once per visitor (keyed by session id) per
     * 30 minutes, so refreshes and bots don't inflate the counter.
     */
    protected function countView(Post $post): void
    {
        if (cache()->add("post.views.{$post->id}.".session()->getId(), true, now()->addMinutes(30))) {
            $post->views = (int) $post->views + 1;
            $post->save();
        }
    }

    /**
     * The min/max price the shop facet slider spans, cached because it is the
     * same answer for every shop page load and changes only when the catalog
     * does.
     *
     * @return array{0: float, 1: float}
     */
    protected function priceBounds(): array
    {
        return ContentCache::remember('shop-price-bounds', function () {
            return [
                (float) Product::active()->min('price') ?: 0,
                (float) Product::active()->max('price') ?: 100000,
            ];
        });
    }
}
