<?php

namespace App\Support\Seo;

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Support\Locale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * schema.org JSON-LD, as one @graph per page.
 *
 * The open graph tags describe a page to a social network; JSON-LD describes the
 * same page to a search engine, and it is the only format that can say what a
 * thing *is* rather than what it is called. A product page carrying a Product
 * node with its price and stock is what can earn a price/star snippet; a
 * meta description never can.
 *
 * Everything is emitted as a single @graph with @id cross-references rather than
 * as a separate <script> per type. That is not a style preference: without @ids
 * a crawler sees a fresh, unlinked Organization on every page, and the
 * association between the product it is reading and the site selling it is
 * inferred from string matching instead of stated. One graph per page keeps the
 * publisher reference honest, and keeps the bytes down.
 *
 * Two rules run through all of it. Nothing is invented — a node that would need
 * a fact the database does not have is emitted smaller or not at all, because a
 * wrong price in structured data is worse for a shop than no structured data.
 * And nothing is emitted on a noindex page, where it could never be read.
 */
final class Schema
{
    /**
     * The whole document as JSON, ready to drop inside a <script> element, or
     * null for a page that should carry none.
     *
     * The encoding lives here rather than in the Blade partial for one reason:
     * a literal '@context' written in a template is read by Blade as the
     *
     * @context directive, so the key is silently compiled into a PHP echo and
     * the document ships with no @context at all. It is the one string in this
     * feature that cannot be spelled out in a view.
     */
    public static function document(SeoData $seo, ?Page $page, ?Request $request = null): ?string
    {
        $graph = self::graph($seo, $page, $request);

        if ($graph === []) {
            return null;
        }

        // JSON_HEX_TAG so a closing script tag typed into any admin field can
        // never end the element early, and the slashes are left readable.
        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => $graph],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
        );
    }

    /**
     * The @graph for this page, or an empty array for a page that should carry
     * none. $seo is passed rather than re-resolved so the structured data and
     * the meta tags can never describe two different pages.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function graph(SeoData $seo, ?Page $page, ?Request $request = null): array
    {
        $request ??= request();
        // Structured data on a noindex page can never be surfaced, so the
        // customer's cart and account pages skip it entirely.
        if (in_array('noindex', $seo->robots, true)) {
            return [];
        }

        $graph = [self::organization(), self::website()];

        $product = $page?->type === 'product' ? $page->product : null;
        $post = $page?->type === 'post' ? $page->post : null;

        $trail = self::trail($seo, $page, $product, $post);

        $graph[] = self::webPage($seo, $page, $product, $post, (bool) $trail);

        if ($trail !== []) {
            $graph[] = self::breadcrumbs($seo, $trail);
        }

        if ($product) {
            $graph[] = self::product($seo, $page, $product);
        }

        if ($post) {
            $graph[] = self::article($seo, $page, $post);
        }

        return self::withoutEmptyNodes($graph);
    }

    /**
     * The organisation behind the site: the publisher every article is attributed
     * to, and the thing that ties a product to a store rather than to nowhere.
     *
     * @return array<string, mixed>
     */
    private static function organization(): array
    {
        $origin = Url::origin();
        $name = Setting::get('site_name') ?: config('app.name');
        $logo = Setting::get('site_icon');

        return [
            '@type' => 'Organization',
            '@id' => $origin.'/#organization',
            'name' => $name,
            'url' => $origin.'/',
            // schema.org wants an ImageObject here, not a bare path: a logo
            // needs a URL and a declared size to be accepted.
            'logo' => $logo ? [
                '@type' => 'ImageObject',
                'url' => self::absolute($logo),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function website(): array
    {
        $origin = Url::origin();
        $name = Setting::get('site_name') ?: config('app.name');

        return [
            '@type' => 'WebSite',
            '@id' => $origin.'/#website',
            'name' => $name,
            'url' => $origin.'/',
            'inLanguage' => Locale::current(),
            'publisher' => ['@id' => $origin.'/#organization'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function webPage(SeoData $seo, ?Page $page, ?Product $product, ?Post $post, bool $hasBreadcrumbs): array
    {
        $origin = Url::origin();

        return [
            '@type' => $product ? 'CollectionPage' : 'WebPage',
            '@id' => $seo->canonical.'#webpage',
            'url' => $seo->canonical,
            'name' => $seo->title,
            'description' => $seo->description,
            'inLanguage' => Locale::current(),
            'isPartOf' => ['@id' => $origin.'/#website'],
            'primaryImageOfPage' => $seo->ogImage ? ['@id' => $seo->ogImage] : null,
            'breadcrumb' => $hasBreadcrumbs ? ['@id' => $seo->canonical.'#breadcrumb'] : null,
        ];
    }

    /**
     * The trail from the home page down to this one.
     *
     * Only real ancestors. A breadcrumb that invents a level ("Home > Products >
     * Widgets > Widget") to look deeper is a trail Google treats as a soft
     * cloaking attempt, so a page with no hierarchy above it gets a single step.
     *
     * @return array<int, array{title: string, url: string|null}>
     */
    private static function trail(SeoData $seo, ?Page $page, ?Product $product, ?Post $post): array
    {
        $origin = Url::origin();
        $home = ['title' => Setting::get('site_name') ?: config('app.name'), 'url' => $origin.'/'];

        if ($product) {
            $category = $product->categories->first();

            return array_values(array_filter([
                $home,
                ['title' => 'Shop', 'url' => self::route('shop')],
                $category
                    ? ['title' => self::label($category->name), 'url' => Url::url('category/'.$category->page?->slug)]
                    : null,
                ['title' => self::label($product->name), 'url' => $seo->canonical],
            ]));
        }

        if ($post) {
            $category = $post->category;

            return array_values(array_filter([
                $home,
                ['title' => 'Blog', 'url' => self::route('blog')],
                $category
                    ? ['title' => self::label($category->name), 'url' => Url::url('blog/'.($category->page?->slug ?? $category->slug))]
                    : null,
                ['title' => self::label($post->title), 'url' => $seo->canonical],
            ]));
        }

        if ($page && $page->slug === 'home') {
            return [$home];
        }

        if ($page) {
            return [$home, ['title' => self::label($page->title), 'url' => $seo->canonical]];
        }

        return [$home];
    }

    /**
     * @param  array<int, array{title: string, url: string|null}>  $trail
     * @return array<string, mixed>
     */
    private static function breadcrumbs(SeoData $seo, array $trail): array
    {
        return [
            '@type' => 'BreadcrumbList',
            '@id' => $seo->canonical.'#breadcrumb',
            'itemListElement' => array_values(array_map(
                fn (array $step, int $index) => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $step['title'],
                    'item' => $step['url'],
                ]),
                $trail,
                array_keys($trail),
            )),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function product(SeoData $seo, ?Page $page, Product $product): array
    {
        // The discount price is what the page shows and what a customer pays, so
        // it is what the Offer has to say. Stating the undiscounted price in
        // structured data is how a shop earns a "price is wrong" complaint.
        $price = $product->discount_price ?: $product->price;

        $images = self::productImages($product);

        // Only approved reviews count, and only if there are any: a star rating
        // with a reviewCount of 0 is a rejected rich result, and one that hides
        // the very ratings it was meant to surface.
        $rating = Review::query()
            ->where('reviewable_type', $product->getMorphClass())
            ->where('reviewable_id', $product->id)
            ->approved()
            ->get(['rating']);

        $node = [
            '@type' => 'Product',
            '@id' => $seo->canonical.'#product',
            'name' => self::label($product->name),
            'url' => $seo->canonical,
            'description' => $seo->description,
            'sku' => $product->sku,
            'image' => $images ?: null,
            'brand' => $product->brand
                ? ['@type' => 'Brand', 'name' => self::label($product->brand->name)]
                : null,
            'offers' => [
                '@type' => 'Offer',
                'url' => $seo->canonical,
                'price' => (float) $price,
                'priceCurrency' => Setting::get('currency_code', 'BDT'),
                'availability' => ($product->quantity ?? 0) > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
            ],
            'aggregateRating' => $rating->isNotEmpty() ? [
                '@type' => 'AggregateRating',
                'ratingValue' => round($rating->avg('rating'), 1),
                'reviewCount' => $rating->count(),
                'bestRating' => 5,
                'worstRating' => 1,
            ] : null,
        ];

        // An upcoming product has no price yet and no stock; offering one anyway
        // would put a 0.00 in the SERP for something that cannot be bought.
        if ($product->is_upcoming) {
            unset($node['offers'], $node['aggregateRating']);
            $node['availability'] = 'https://schema.org/PreOrder';
        }

        return $node;
    }

    /**
     * @return array<string, mixed>
     */
    private static function article(SeoData $seo, ?Page $page, Post $post): array
    {
        return [
            // BlogPosting rather than Article: it is the narrower type, and it is
            // what a dated editorial page actually is.
            '@type' => 'BlogPosting',
            '@id' => $seo->canonical.'#article',
            'url' => $seo->canonical,
            'mainEntityOfPage' => ['@id' => $seo->canonical.'#webpage'],
            'headline' => self::label($post->title),
            'description' => $seo->description,
            'image' => $seo->ogImage ? [$seo->ogImage] : null,
            'inLanguage' => Locale::current(),
            'datePublished' => optional($post->published_at ?: $post->created_at)->toIso8601String(),
            'dateModified' => optional($post->updated_at)->toIso8601String(),
            'author' => $post->user
                ? ['@type' => 'Person', 'name' => $post->user->name]
                : null,
            'publisher' => ['@id' => Url::origin().'/#organization'],
            'articleSection' => $post->category ? self::label($post->category->name) : null,
            'wordCount' => $post->content ? str_word_count(strip_tags(json_encode($post->content))) : null,
        ];
    }

    /**
     * Every image a shopper can see on the page, absolute, the featured one
     * first — Google takes the first entry as the representative image.
     *
     * @return array<int, string>
     */
    private static function productImages(Product $product): array
    {
        $paths = $product->gallery->pluck('url')->filter()->all();

        if ($product->featured_image) {
            array_unshift($paths, $product->featured_image);
        }

        return array_values(array_unique(array_map(self::absolute(...), $paths)));
    }

    private static function absolute(string $path): string
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
            ? $path
            : Url::url($path);
    }

    /**
     * A translatable field read for the active locale, stringified, falling back
     * to the primary locale and then to whatever is there — the same three-step
     * the rest of the storefront uses for a translatable value.
     */
    private static function label(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (! is_array($value)) {
            return (string) $value;
        }

        $current = $value[Locale::current()] ?? null;

        if (filled($current)) {
            return (string) $current;
        }

        $primary = $value[Locale::primary()] ?? null;

        if (filled($primary)) {
            return (string) $primary;
        }

        return (string) (reset($value) ?: '');
    }

    /**
     * A named route's URL, or null when this theme doesn't register it — the
     * portfolio theme has no shop and no blog, and a breadcrumb pointing at a
     * 404 is worse than no breadcrumb.
     */
    private static function route(string $name): ?string
    {
        return Route::has($name) ? route($name) : null;
    }

    /**
     * Drops keys whose value is null.
     *
     * "key": null is not the same as an absent key to a structured-data parser:
     * a null logo or a null price is a field the site claims to know and does
     * not. Absent is the honest way to say "we don't have this".
     *
     * @param  array<int, array<string, mixed>>  $graph
     * @return array<int, array<string, mixed>>
     */
    private static function withoutEmptyNodes(array $graph): array
    {
        return array_values(array_map(
            fn (array $node) => array_filter($node, fn ($value) => $value !== null && $value !== []),
            $graph,
        ));
    }
}
