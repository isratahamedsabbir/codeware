<?php

namespace App\Http\Controllers;

use App\Models\CmsSection;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductVendor;
use App\Models\Setting;
use App\Models\Tag;
use App\Support\Favorites;
use App\Support\Locale;
use App\Support\Themes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class FrontendController extends Controller
{
    /**
     * The public site root — renders the admin-selected theme's homepage,
     * populated with the "home" page's CMS sections.
     */
    public function home()
    {
        $theme = Themes::active();

        $homePage = Page::where('slug', 'home')->first();

        $sections = $homePage
            ? CmsSection::cachedForPage($homePage->id)
            : collect();

        return view("frontend.themes.{$theme}.home", [
            'page' => $homePage,
            'sections' => $sections,
            'title' => $homePage?->seo_title ?: (Setting::get('seo_meta_title') ?: Setting::get('site_name')),
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => 'home',
            'showVendorLogin' => $this->showVendorLogin(),
        ]);
    }

    /**
     * Any other standalone page (About, Contact, FAQ, ...) — same rendering as
     * home(), just scoped to the requested page's own CMS sections instead of
     * the "home" page's. Same view (`page.blade.php`) across every theme.
     */
    public function page(string $slug)
    {
        $theme = Themes::active();

        $page = Page::where('slug', $slug)->where('type', 'page')->where('status', 'active')->firstOrFail();

        $sections = CmsSection::cachedForPage($page->id);

        return view("frontend.themes.{$theme}.page", [
            'page' => $page,
            'sections' => $sections,
            'title' => $page->seo_title ?: $page->getTranslation('title', 'en', false),
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => $slug,
            'showVendorLogin' => $this->showVendorLogin(),
        ]);
    }

    /**
     * The storefront catalog — every active product, facet-filterable by
     * category/brand/tag, free-text search, and sort order, all via query
     * params so the facets are just plain links. Rendered through the active
     * theme's shop template when it ships one, otherwise the ecommerce
     * theme's (see Themes::view()).
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
            $query->where(function (Builder $q) use ($term, $locale) {
                $q->where('name->en', 'like', "%{$term}%");

                if ($locale !== 'en') {
                    $q->orWhere("name->{$locale}", 'like', "%{$term}%");
                }
            });
        }

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

        return view('frontend.themes.'.Themes::view('shop'), [
            'products' => $query->paginate(Setting::perPage())->withQueryString(),
            'categories' => $this->shopCategories(),
            'brands' => $this->shopBrands(),
            'tags' => $this->shopTags(),
            'filters' => [
                'category' => (string) $request->query('category', ''),
                'brand' => (string) $request->query('brand', ''),
                'tag' => (string) $request->query('tag', ''),
                'search' => (string) $request->query('search', ''),
                'sort' => (string) $request->query('sort', ''),
            ],
            'title' => Setting::get('seo_meta_title') ?: Setting::get('site_name'),
            'page' => null,
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => 'shop',
            'showVendorLogin' => $this->showVendorLogin(),
        ]);
    }

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

        $related = $product->categories->isNotEmpty()
            ? Product::active()
                ->with(['categories', 'brand', 'tags', 'page'])
                ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $product->categories->pluck('id')))
                ->where('id', '!=', $product->id)
                ->orderBy('sort_order')
                ->limit(4)
                ->get()
            : collect();

        return view('frontend.themes.'.Themes::view('product'), [
            'product' => $product,
            'related' => $related,
            'sections' => $product->page ? CmsSection::cachedForPage($product->page->id) : collect(),
            'page' => $product->page,
            'title' => $product->page?->seo_title ?: $product->name,
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => $product->slug,
            'showVendorLogin' => $this->showVendorLogin(),
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

        return view('frontend.themes.'.Themes::view('category'), [
            'category' => $category,
            'children' => $children,
            'products' => $products,
            'sections' => $category->page ? CmsSection::cachedForPage($category->page->id) : collect(),
            'page' => $category->page,
            'title' => $category->page?->seo_title ?: $category->name,
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => $category->slug,
            'showVendorLogin' => $this->showVendorLogin(),
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

        $products = $this->productQuery()
            ->where('brand_id', $brand->id)
            ->paginate(Setting::perPage())
            ->withQueryString();

        return view('frontend.themes.'.Themes::view('brand'), [
            'brand' => $brand,
            'products' => $products,
            'sections' => collect(),
            'page' => null,
            'title' => $brand->name,
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => $brand->slug,
            'showVendorLogin' => $this->showVendorLogin(),
        ]);
    }

    /**
     * A tag landing page — tag name then the tag's products. Tags, like
     * brands, have no Page/slug of their own; the URL slug is derived from
     * the primary locale name (see resolveTag()).
     */
    public function tag(string $slug)
    {
        $tag = $this->resolveTag($slug);

        abort_unless($tag, 404, 'Unknown tag.');

        $products = $this->productQuery()
            ->whereHas('tags', fn ($q) => $q->whereKey($tag->id))
            ->paginate(Setting::perPage())
            ->withQueryString();

        return view('frontend.themes.'.Themes::view('tag'), [
            'tag' => $tag,
            'products' => $products,
            'sections' => collect(),
            'page' => null,
            'title' => $tag->name,
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => $tag->slug,
            'showVendorLogin' => $this->showVendorLogin(),
        ]);
    }

    /**
     * The saved-favorites page — every product this visitor favorited (session
     * for guests, per-user rows once signed in, see App\Support\Favorites).
     */
    public function favorites()
    {
        $products = Favorites::products()
            ->with(['categories.page', 'brand', 'tags', 'page'])
            ->orderBy('sort_order')
            ->paginate(Setting::perPage())
            ->withQueryString();

        return view('frontend.themes.'.Themes::view('favorites'), [
            'products' => $products,
            'page' => null,
            'sections' => collect(),
            'title' => __('My Favorites'),
            'navPages' => $this->navPages(),
            'menuItems' => $this->frontendMenuItems(),
            'currentSlug' => 'favorites',
            'showVendorLogin' => $this->showVendorLogin(),
        ]);
    }

    /**
     * The base query for every product browse page: only active products,
     * with everything a card or detail view needs eager-loaded, ordered by
     * sort_order like the admin and public API.
     */
    private function productQuery(): Builder
    {
        return Product::active()
            ->with(['categories.page', 'brand', 'tags', 'page'])
            ->orderBy('sort_order');
    }

    /**
     * Active top-level categories (with per-child subtrees attached) and the
     * count of active products in each — used by the shop sidebar. The
     * depth-first flattening on ProductCategory::tree() drives the facet list.
     */
    private function shopCategories(): Collection
    {
        $count = fn (Builder $q) => $q->where('products.status', 'active');

        return ProductCategory::active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->with(['children' => function (HasMany $q) use ($count) {
                $q->active()->with('page')
                    ->withCount(['products' => $count]);
            }])
            ->orderBy('sort_order')
            ->get();
    }

    private function shopBrands(): Collection
    {
        return ProductBrand::active()
            ->withCount(['products' => fn ($q) => $q->active()])
            ->orderBy('sort_order')
            ->get();
    }

    private function shopTags(): Collection
    {
        return Tag::where('status', 'active')->orderBy('sort_order')->get();
    }

    /**
     * Finds a brand by its URL slug — the slug is computed from the primary
     * locale name (same separators as Slug::make), since brands have no Page.
     */
    private function resolveBrand(string $slug): ?ProductBrand
    {
        return ProductBrand::active()->get()
            ->first(fn (ProductBrand $brand) => $this->taxonomyKey($brand) === Str::slug($slug, '-'));
    }

    /**
     * Finds a tag by its URL slug — same derivation as resolveBrand().
     */
    private function resolveTag(string $slug): ?Tag
    {
        return Tag::where('status', 'active')->get()
            ->first(fn (Tag $tag) => $this->taxonomyKey($tag) === Str::slug($slug, '-'));
    }

    private function taxonomyKey(Model $item): string
    {
        $name = (string) ($item->getTranslation('name', Locale::primary(), false) ?: $item->getTranslation('name', 'en', false));

        return Str::slug(is_array($name) ? reset($name) : $name, '-');
    }

    /**
     * Every standalone page (Home, About, Contact, FAQ, ...), in the admin's
     * chosen order — used as the site nav by the "default" theme, so
     * adding/reordering pages in the admin updates it automatically.
     */
    private function navPages()
    {
        return Page::ofType('page')->published()->orderBy('sort_order')->get();
    }

    /**
     * The "Frontend" menu (see FrontendMenuSeeder, and /admin/menu), managed
     * by hand rather than auto-generated from the page list — used as the site
     * nav by the portfolio and ecommerce themes.
     */
    private function frontendMenuItems()
    {
        return MenuItem::where('group', 'frontend')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Whether the Vendor Login link should appear — hidden whenever nobody
     * could actually sign into the vendor portal: the 'vendor' role itself
     * deactivated (see Roles\Index::toggleStatus, access-vendor-portal gate)
     * or no active vendor exists for a user to be assigned to.
     */
    private function showVendorLogin(): bool
    {
        $vendorRoleActive = Role::where('name', 'vendor')->where('status', 'active')->exists();

        return $vendorRoleActive && ProductVendor::active()->exists();
    }
}
