<?php

namespace App\Support\Seo;

/**
 * Everything a page says about itself to a search engine, resolved once.
 *
 * Built by SeoResolver from the paired Page, the global "Global SEO" settings and
 * the route's RouteSeo profile, so that partials/seo-meta.blade.php only has to
 * print it and every theme gets the same answer. Immutable — read it, don't poke
 * at it; change the resolution in SeoResolver instead.
 */
final class SeoData
{
    /**
     * @param  array<int, string>  $robots  directives other than index/nofollow, e.g. max-snippet:-1
     * @param  array<int, array{hreflang: string, href: string}>  $alternates
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $canonical,
        public readonly array $robots,
        public readonly string $ogType,
        public readonly string $ogUrl,
        public readonly ?string $ogTitle,
        public readonly ?string $ogDescription,
        public readonly ?string $ogImage,
        public readonly ?string $ogImageAlt,
        public readonly string $twitterCard,
        public readonly ?string $twitterSite,
        public readonly ?string $twitterTitle,
        public readonly ?string $twitterDescription,
        public readonly ?string $twitterImage,
        public readonly string $siteName,
        public readonly string $locale,
        public readonly array $alternates = [],
    ) {}

    public function isIndexable(): bool
    {
        return ! in_array('noindex', $this->robots, true);
    }

    /**
     * The robots value as a single attribute — `noindex, nofollow, max-snippet:-1`.
     * Null when the page carries no directives at all, so the caller can skip the
     * tag entirely: an absent robots tag already means index,follow, and writing
     * that out on every page is noise.
     */
    public function robotsContent(): ?string
    {
        return $this->robots === [] ? null : implode(', ', $this->robots);
    }

    /**
     * The same values the public API exposes as `meta_data`, so the API and the
     * head can never drift apart.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'canonical' => $this->canonical,
            'robots' => $this->robotsContent(),
            'og' => [
                'type' => $this->ogType,
                'url' => $this->ogUrl,
                'title' => $this->ogTitle,
                'description' => $this->ogDescription,
                'image' => $this->ogImage,
                'image_alt' => $this->ogImageAlt,
                'site_name' => $this->siteName,
                'locale' => $this->locale,
            ],
            'twitter' => array_filter([
                'card' => $this->twitterCard,
                'site' => $this->twitterSite,
                'title' => $this->twitterTitle,
                'description' => $this->twitterDescription,
                'image' => $this->twitterImage,
            ], fn ($value) => filled($value)),
        ];
    }
}
