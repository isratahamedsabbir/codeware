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
 *
 * The six text fields are translatable, one value per locale, so the admin types
 * them through the same `<x-admin-locale-tabs>` every other translated field
 * uses rather than a second, English-only set of inputs. That means a component
 * using this trait must also use HasTranslatableFields (all four do).
 */
trait HasSeoFields
{
    /** @var array<string, string> */
    public array $seo_title = [];

    /** @var array<string, string> */
    public array $seo_description = [];

    public ?string $og_image = null;

    public string $ogImagePickerId = '';

    /** @var array<string, string> */
    public array $og_title = [];

    /** @var array<string, string> */
    public array $og_description = [];

    public ?string $twitter_image = null;

    public string $twitterImagePickerId = '';

    /** @var array<string, string> */
    public array $twitter_title = [];

    /** @var array<string, string> */
    public array $twitter_description = [];

    public bool $no_index = false;

    public bool $no_follow = false;

    #[Validate('nullable|string|max:255')]
    public string $canonical_base = '';

    #[Validate('nullable|string|max:255')]
    public string $canonical_slug = '';

    /**
     * The translatable half of the section, in the order the admin sees them —
     * one entry per field, each a {field => base rules} pair. Kept here so the
     * per-locale rules in rules() and the per-locale inputs in the partial
     * cannot drift apart: a field added to one and not the other is the kind of
     * thing that silently stops being editable.
     *
     * @return array<string, array<string, string|array<int, string>>>
     */
    public static function seoTranslatableFields(): array
    {
        return [
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'twitter_title' => 'nullable|string|max:255',
            'twitter_description' => 'nullable|string',
        ];
    }

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
     *
     * The translatable half is loaded for every locale that has one saved, not
     * just the active one, so switching tabs in the form shows what is already
     * there instead of an empty box the admin might overwrite.
     */
    public function hydrateSeoFieldsFromPage(?Page $page): void
    {
        if (! $page) {
            return;
        }

        $this->hydrateTranslatable($page, array_keys(self::seoTranslatableFields()));

        $this->og_image = $page->og_image ?? null;
        $this->twitter_image = $page->twitter_image ?? null;
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
     * data array.
     *
     * A blank string becomes null in the translatable half so clearing a field
     * in the admin actually clears it on the Page, not just skips writing it —
     * and a locale the admin never touched is left out entirely rather than
     * written as an empty string, which would look like a deliberate
     * "this language has no meta title" to the fallback chain in SeoResolver.
     */
    public function seoPagePayload(): array
    {
        $payload = [
            'og_image' => $this->og_image ?: null,
            'twitter_image' => $this->twitter_image ?: null,
            'no_index' => $this->no_index,
            'no_follow' => $this->no_follow,
            'canonical_base' => $this->canonical_base ?: null,
            'canonical_slug' => $this->canonical_slug ?: null,
        ];

        foreach (self::seoTranslatableFields() as $field => $rules) {
            $values = [];

            foreach ($this->$field as $locale => $value) {
                $values[$locale] = filled($value) ? $value : null;
            }

            // Nothing typed anywhere means NULL, not `[]` or `{"en":null}` — a
            // page with no SEO fields should look untouched to the admin, and to
            // every query that asks whether one was ever set. Once one locale has
            // a value the whole set is written, so a locale the admin cleared is
            // actually cleared rather than silently keeping its old copy.
            $payload[$field] = array_filter($values, fn ($value) => $value !== null) === []
                ? null
                : $values;
        }

        return $payload;
    }

    /**
     * @return array<int, string>
     */
    public function canonicalBaseOptions(): array
    {
        return json_decode(Setting::get('seo_canonical_urls', '[]') ?: '[]', true) ?: [];
    }
}
