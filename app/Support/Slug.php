<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Single source of truth for slug formatting and uniqueness. Products, Posts,
 * ProductCategories, and PostCategories each own their slug and push it into
 * their paired Page on save (see each Form's persist*() method); Page never
 * writes it back — for a linked Page, Pages/Form.php re-reads the slug from
 * the entity on every save instead of trusting its own field, so the entity
 * stays the single source of truth and the two can never drift apart. A slug
 * used anywhere must still be globally unique, not just within its own table.
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
     * Validation rules for a slug field: always checked against `pages` (every
     * slug-bearing entity has one), plus the entity's own table when given —
     * a defensive second check in case a row is ever missing its paired page.
     *
     * @return array<int, Unique>
     */
    public static function uniqueRules(?int $pageId, ?string $entityTable = null, ?int $entityId = null): array
    {
        $rules = [Rule::unique('pages', 'slug')->ignore($pageId)];

        if ($entityTable) {
            $rules[] = Rule::unique($entityTable, 'slug')->ignore($entityId);
        }

        return $rules;
    }

    /**
     * Same check as uniqueRules(), but as a plain boolean for a live
     * red/green indicator while the admin is still typing — an empty slug
     * counts as available (nothing to flag yet).
     */
    public static function isAvailable(string $slug, ?int $pageId, ?string $entityTable = null, ?int $entityId = null): bool
    {
        if ($slug === '') {
            return true;
        }

        $taken = DB::table('pages')
            ->where('slug', $slug)
            ->when($pageId, fn ($q) => $q->where('id', '!=', $pageId))
            ->exists();

        if ($taken) {
            return false;
        }

        if ($entityTable) {
            $taken = DB::table($entityTable)
                ->where('slug', $slug)
                ->when($entityId, fn ($q) => $q->where('id', '!=', $entityId))
                ->exists();
        }

        return ! $taken;
    }
}
