<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Single source of truth for slug formatting and uniqueness across Products,
 * Posts, ProductCategories, PostCategories, and Pages — every one of those
 * always has a 1:1 paired Page row sharing the exact same slug (see each
 * Form's persist*() method / Pages/Form.php's entity sync), so a slug used
 * anywhere must be globally unique, not just within its own table.
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
