<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Single source of truth for slug formatting and uniqueness. Page owns the
 * slug for every slug-bearing entity — Products, Posts, ProductCategories,
 * and PostCategories have no slug column of their own; they read it via an
 * accessor that proxies to their paired Page (see each model's slug()
 * accessor), and every admin Form/REST controller writes the typed slug onto
 * the paired Page only. Uniqueness is therefore just a `pages.slug` check.
 */
class Slug
{
    /**
     * Underscore separator, special characters stripped — e.g. "Men's Shoes!"
     * becomes "mens_shoes", "Café Menu" becomes "cafe_menu".
     */
    public static function make(string $value): string
    {
        return Str::slug($value, '_');
    }

    /**
     * Lowercases a manually-typed slug. make() already returns lowercase, but
     * an admin can type directly into the slug field with any casing — this
     * is the single place that normalizes it before it ever reaches the DB.
     */
    public static function lower(string $slug): string
    {
        return Str::lower($slug);
    }

    /**
     * Validation rules for a slug field: checked against `pages`, the only
     * table that still stores one.
     *
     * @return array<int, Unique>
     */
    public static function uniqueRules(?int $pageId): array
    {
        return [Rule::unique('pages', 'slug')->ignore($pageId)];
    }

    /**
     * Same check as uniqueRules(), but as a plain boolean for a live
     * red/green indicator while the admin is still typing — an empty slug
     * counts as available (nothing to flag yet).
     */
    public static function isAvailable(string $slug, ?int $pageId): bool
    {
        if ($slug === '') {
            return true;
        }

        return ! DB::table('pages')
            ->where('slug', $slug)
            ->when($pageId, fn ($q) => $q->where('id', '!=', $pageId))
            ->exists();
    }
}
