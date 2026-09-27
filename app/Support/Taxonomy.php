<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Type;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Validation rules for ids that point into the shared taxonomy table
 * (`categories`). The table is one table with two discriminator columns, so an
 * id on its own is ambiguous: the Brand form's `brand_id` and the Post form's
 * `category_id` are both bare `categories.id` values, and without narrowing they
 * would each happily accept the other's rows.
 */
class Taxonomy
{
    /**
     * A rule that only accepts ids belonging to the given taxonomy model —
     * e.g. Taxonomy::rule(ProductCategory::class) for a Product form's
     * `category_ids.*`, or Taxonomy::rule(Tag::class) for a post's `tag_ids.*`.
     *
     * Narrows the row's `kind` (is this a category, a brand or a tag at all) and,
     * when the caller names a pool or the model is locked to one, its `type_id`.
     * A brand or a tag belongs to exactly one of Product/Post, so a form that
     * offers a pool-specific picker must say which pool it is accepting —
     * otherwise it would happily take the other pool's rows and put a product
     * tag on a post. Callers with no pool in mind (the Brand and Tag admin
     * lists, which manage both pools at once) can omit it and check kind only.
     *
     * @param  class-string<Category>  $model
     */
    public static function rule(string $model, ?string $type = null, ?int $ignore = null): Exists
    {
        $type ??= defined($model.'::TYPE') ? $model::TYPE : null;

        $rule = Rule::exists('categories', 'id')
            ->where(fn ($query) => $query->where('kind', $model::KIND));

        if ($type !== null) {
            $rule->where(fn ($query) => $query->whereIn('type_id', Type::subquery($type)));
        }

        return $ignore === null ? $rule : $rule->ignore($ignore);
    }

    /**
     * Same check, as a plain array of rules for a validated property — a single
     * `category_id` rather than a `category_ids.*` list, and a nullable one,
     * since every taxonomy picker on the admin forms is optional.
     *
     * @param  class-string<Category>  $model
     * @return array<int, mixed>
     */
    public static function nullableRule(string $model, ?string $type = null, ?int $ignore = null): array
    {
        return ['nullable', 'integer', self::rule($model, $type, $ignore)];
    }

    /**
     * @param  class-string<Category>  $model
     * @return array<int, mixed>
     */
    public static function eachRule(string $model, ?string $type = null): array
    {
        return ['integer', self::rule($model, $type)];
    }
}
