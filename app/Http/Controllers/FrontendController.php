<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use App\Models\CmsSection;
use App\Models\Order;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\Tag;
use App\Support\ContentCache;
use App\Support\Favorites;
use App\Support\Features;
use App\Support\Frontend;
use App\Support\Locale;
use App\Support\ProductCatalog;
use App\Support\Themes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class FrontendController extends Controller
{
    /**
     * The public site root — renders the admin-selected theme's homepage,
     * populated with the "home" page's CMS sections.
     */
    public function home()
    {
        $theme = Themes::active();

        $homePage = Frontend::homePage();

        $sections = $homePage
            ? CmsSection::cachedForPage($homePage->id)
            : collect();

        return view("frontend.themes.{$theme}.home", [
            'page' => $homePage,
            'sections' => $sections,
            'title' => $homePage?->seo_title ?: (Setting::get('seo_meta_title') ?: Setting::get('site_name')),
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => 'home',
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
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
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => $slug,
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
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

        [$priceMin, $priceMax] = ContentCache::remember('shop-price-bounds', function () {
            return [
                (float) Product::active()->min('price') ?: 0,
                (float) Product::active()->max('price') ?: 100000,
            ];
        });

        $priceBounds = (object) ['min' => $priceMin, 'max' => $priceMax];

        return view('frontend.themes.'.Themes::view('shop'), [
            'products' => $query->paginate(Setting::perPage())->withQueryString(),
            'categories' => $this->shopCategories(),
            'brands' => $this->shopBrands(),
            'tags' => $this->shopTags(),
            'attributeFacets' => ProductCatalog::attributeFacets(),
            'priceBounds' => $priceBounds,
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
            'title' => Setting::get('seo_meta_title') ?: Setting::get('site_name'),
            'page' => null,
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => 'shop',
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
        ]);
    }

    /**
     * The storefront filter set from the request — attributes are read as
     * `attributes[Color]=Red` while price and type use their flat query
     * params. Mirrored by the public API's ProductController.
     *
     * @return array{attributes: array<string, string>, min_price: mixed, max_price: mixed, type: string}
     */
    private function filtersFrom(Request $request): array
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
            ->limit(4)
            ->get();

        if ($related->isEmpty() && $product->categories->isNotEmpty()) {
            $related = Product::active()
                ->with(['categories', 'brand', 'tags', 'page'])
                ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $product->categories->pluck('id')))
                ->where('id', '!=', $product->id)
                ->orderBy('sort_order')
                ->limit(4)
                ->get();
        }

        return view('frontend.themes.'.Themes::view('product'), [
            'product' => $product,
            'related' => $related,
            'sections' => $product->page ? CmsSection::cachedForPage($product->page->id) : collect(),
            'page' => $product->page,
            'title' => $product->page?->seo_title ?: $product->name,
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => $product->slug,
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
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
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => $category->slug,
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
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
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => $brand->slug,
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
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
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => $tag->slug,
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
        ]);
    }

    /**
     * The blog feed — every published post, newest first, optionally narrowed
     * to a category via ?category (category links come from the category's
     * paired Page slug). Rendered through the active theme's blog template.
     */
    public function blog(Request $request)
    {
        $posts = Post::published()
            ->with(['page', 'category.page', 'user:id,name', 'tags'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($slug = $request->query('category')) {
            $posts->whereHas('category.page', fn ($q) => $q->where('slug', $slug));
        }

        $categories = PostCategory::where('status', 'active')
            ->with('page')
            ->withCount(['posts' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (PostCategory $category) => $category->page !== null);

        return view('frontend.themes.'.Themes::view('blog'), [
            'posts' => $posts->paginate(Setting::perPage())->withQueryString(),
            'categories' => $categories,
            'page' => null,
            'sections' => collect(),
            'title' => __('Blog'),
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => 'blog',
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
        ]);
    }

    /**
     * A single blog post — title, meta (author, date, reading time), category,
     * tags, and the post's paired Page's CMS sections (Puck-built content lives
     * on the Page, just like products). Resolved by the paired Page's slug.
     */
    public function post(string $slug)
    {
        $post = Post::published()
            ->with(['page', 'category.page', 'user:id,name', 'tags'])
            ->whereHas('page', fn ($q) => $q->where('slug', $slug))
            ->first();

        abort_unless($post, 404, 'Unknown post.');

        $this->countView($post);

        $sections = $post->page ? CmsSection::cachedForPage($post->page->id) : collect();

        $related = Post::published()
            ->with('page')
            ->whereHas('page')
            ->where('id', '!=', $post->id)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return view('frontend.themes.'.Themes::view('post'), [
            'post' => $post,
            'related' => $related,
            'sections' => $sections,
            'page' => $post->page,
            'title' => $post->page?->seo_title ?: $post->getTranslation('title', 'en', false),
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => $post->page?->slug ?? 'blog',
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
        ]);
    }

    /**
     * Counts a post view, but only once per visitor (keyed by session id) per
     * 30 minutes, so refreshes and bots don't inflate the counter.
     */
    private function countView(Post $post): void
    {
        if (cache()->add("post.views.{$post->id}.".session()->getId(), true, now()->addMinutes(30))) {
            $post->views = (int) $post->views + 1;
            $post->save();
        }
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
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => 'favorites',
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
        ]);
    }

    /**
     * The shopping cart page — the session cart's lines, quantity controls and
     * summary live in the CartPage Livewire component (see App\Support\Cart).
     * Orderable only while the orders feature is on (the route group gates it).
     */
    public function cart()
    {
        return view('frontend.themes.'.Themes::view('cart'), [
            'page' => null,
            'sections' => collect(),
            'title' => __('My cart'),
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => 'cart',
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
        ]);
    }

    /**
     * The checkout page — cart summary plus a customer form that turns the cart
     * into an Order (see the Checkout Livewire component).
     */
    public function checkout()
    {
        return view('frontend.themes.'.Themes::view('checkout'), [
            'page' => null,
            'sections' => collect(),
            'title' => __('Checkout'),
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => 'checkout',
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
        ]);
    }

    /**
     * The post-checkout confirmation page. Only reachable for the order that
     * was just placed in this session — anything else bounces back to the shop,
     * so an arbitrary (or guessed) order number can't be browsed.
     */
    public function orderConfirmation(string $orderNumber)
    {
        if (session('placed_order') !== $orderNumber) {
            return redirect()->route('shop');
        }

        $order = Order::with('items.product')->where('order_number', $orderNumber)->firstOrFail();

        return view('frontend.themes.'.Themes::view('order-confirmation'), [
            'order' => $order,
            // The same permanent signed links the invoice QR code points at, so
            // the shopper can view/print or download the invoice without an account.
            'invoiceUrl' => URL::signedRoute('invoices.public.show', ['order' => $order->order_number]),
            'invoiceDownloadUrl' => URL::signedRoute('invoices.public.download', ['order' => $order->order_number]),
            'page' => null,
            'sections' => collect(),
            'title' => __('Order placed'),
            'navPages' => Frontend::navPages(),
            'menuItems' => Frontend::menuItems(),
            'currentSlug' => 'checkout',
            'showVendorLogin' => Frontend::showVendorLogin(),
            'showDeliveryLogin' => Frontend::showDeliveryLogin(),
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
            ->with('page')
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
}
