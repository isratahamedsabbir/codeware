<?php

namespace App\Concerns;

use App\Models\Page;
use App\Models\Setting;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;

/**
 * The full SEO section (Canonical URL, Meta Tags, Open Graph, Twitter Card,
 * Indexing) shared by Pages/Form and every entity form whose entity is paired
 * with a Page (Products, Posts, ProductCategories, PostCategories) — all of
 * them write these fields onto that same Page row. See
 * resources/views/partials/admin-seo-fields.blade.php for the matching markup.
 */
trait HasSeoFields
{
    #[Validate('nullable|string|max:255')]
    public string $seo_title = '';

    #[Validate('nullable|string')]
    public string $seo_description = '';

    public ?string $og_image = null;

    public string $ogImagePickerId = '';

    #[Validate('nullable|string|max:255')]
    public string $og_title = '';

    #[Validate('nullable|string|max:255')]
    public string $og_description = '';

    public ?string $twitter_image = null;

    public string $twitterImagePickerId = '';

    #[Validate('nullable|string|max:255')]
    public string $twitter_title = '';

    #[Validate('nullable|string|max:255')]
    public string $twitter_description = '';

    public bool $no_index = false;

    public bool $no_follow = false;

    #[Validate('nullable|string|max:255')]
    public string $canonical_base = '';

    #[Validate('nullable|string|max:255')]
    public string $canonical_slug = '';

    /**
     * The last auto-generated canonical_slug value, so we know whether the
     * admin has manually diverged from it — same "follow until edited" pattern
     * as autoSlug, but tracking the slug instead of the title.
     */
    public string $autoCanonicalSlug = '';

    public function mountHasSeoFields(): void
    {
        $this->ogImagePickerId = 'og-image-picker-'.Str::uuid()->toString();
        $this->twitterImagePickerId = 'twitter-image-picker-'.Str::uuid()->toString();
    }

    /**
     * Loads the SEO fields from an existing paired Page — or leaves the
     * defaults in place when $page is null (a brand-new entity with no page
     * yet). Call after $this->slug is already set, since the "still
     * following" canonical-slug check below reads it.
     */
    public function hydrateSeoFieldsFromPage(?Page $page): void
    {
        if (! $page) {
            return;
        }

        $this->seo_title = $page->seo_title ?? '';
        $this->seo_description = $page->seo_description ?? '';
        $this->og_image = $page->og_image ?? null;
        $this->og_title = $page->og_title ?? '';
        $this->og_description = $page->og_description ?? '';
        $this->twitter_image = $page->twitter_image ?? null;
        $this->twitter_title = $page->twitter_title ?? '';
        $this->twitter_description = $page->twitter_description ?? '';
        $this->no_index = (bool) $page->no_index;
        $this->no_follow = (bool) $page->no_follow;
        $this->canonical_base = $page->canonical_base ?? '';
        $this->canonical_slug = $page->canonical_slug ?? $page->slug;

        // A never-set (or still-matching) canonical_slug is still following the
        // slug — keep it auto-syncing. One saved as something else is a
        // deliberate override, so leave autoCanonicalSlug unmatchable ('') to
        // stop future title/slug edits from clobbering it.
        if ($page->canonical_slug === null || $page->canonical_slug === $page->slug) {
            $this->autoCanonicalSlug = $this->slug;
        }
    }

    /**
     * Keeps canonical_slug following the slug the same way slug follows the
     * title/name — only while the admin hasn't typed a custom canonical path.
     */
    public function syncCanonicalSlug(): void
    {
        if ($this->canonical_slug === '' || $this->canonical_slug === $this->autoCanonicalSlug) {
            $this->autoCanonicalSlug = $this->slug;
            $this->canonical_slug = $this->autoCanonicalSlug;
        }
    }

    /**
     * The SEO fields shaped for spreading into a Page::updateOrCreate()/update()
     * data array — blank strings become null so clearing a field in the admin
     * actually clears it on the Page, not just skips writing it.
     *
     * @return array<string, mixed>
     */
    public function seoPagePayload(): array
    {
        return [
            'og_image' => $this->og_image ?: null,
            'seo_title' => $this->seo_title ?: null,
            'seo_description' => $this->seo_description ?: null,
            'og_title' => $this->og_title ?: null,
            'og_description' => $this->og_description ?: null,
            'twitter_image' => $this->twitter_image ?: null,
            'twitter_title' => $this->twitter_title ?: null,
            'twitter_description' => $this->twitter_description ?: null,
            'no_index' => $this->no_index,
            'no_follow' => $this->no_follow,
            'canonical_base' => $this->canonical_base ?: null,
            'canonical_slug' => $this->canonical_slug ?: null,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function canonicalBaseOptions(): array
    {
        return json_decode(Setting::get('seo_canonical_urls', '[]') ?: '[]', true) ?: [];
    }
}
