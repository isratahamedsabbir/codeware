<?php

namespace App\Support;

use App\Models\PortfolioExperience;
use App\Models\PortfolioProject;
use App\Models\PortfolioSkill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Read side of the portfolio theme's three content sections (Projects,
 * Experience, Technology) — the active rows for the portfolio home page, cached
 * under the shared content version (App\Support\ContentCache) so an admin write
 * busts them like any other public-facing content.
 *
 * Like CmsSection::cachedForPage(), this caches *raw attributes*
 * (getAttributes(), not toArray()) and re-hydrates into real models on read —
 * never cache Eloquent objects/collections directly, and never toArray(): that
 * would decode the JSON translatable columns into plain arrays, which then blow
 * up when hydrate() re-applies the same cast (double-decoding a PHP array
 * instead of the JSON string it expects). Reading a translatable field off a
 * hydrated model returns the active locale's value, since
 * spatie/laravel-translatable reads app()->getLocale().
 */
class Portfolio
{
    /**
     * @return Collection<int, PortfolioProject>
     */
    public static function projects(): Collection
    {
        $rows = ContentCache::remember('portfolio:projects', fn () => static::snapshot(PortfolioProject::active()));

        return PortfolioProject::hydrate($rows);
    }

    /**
     * @return Collection<int, PortfolioExperience>
     */
    public static function experiences(): Collection
    {
        $rows = ContentCache::remember('portfolio:experiences', fn () => static::snapshot(PortfolioExperience::active()));

        return PortfolioExperience::hydrate($rows);
    }

    /**
     * The active skills filed into their groups — one entry per distinct `group`,
     * keyed by the group name so a template can print the heading and loop its
     * cards. Groups appear in the order their first skill does, so reordering
     * the list screen also reorders the section.
     *
     * @return Collection<string, Collection<int, PortfolioSkill>>
     */
    public static function skillGroups(): Collection
    {
        $rows = ContentCache::remember('portfolio:skill-groups', fn () => static::snapshot(PortfolioSkill::active()));

        return PortfolioSkill::hydrate($rows)->groupBy(fn (PortfolioSkill $skill) => $skill->group);
    }

    /**
     * The active rows as plain attribute arrays, in the order the theme prints
     * them — sort_order first, with the id as a stable tiebreaker for rows
     * sharing one (rows created in the same instant).
     *
     * @param  Builder<Model>  $query
     * @return array<int, array<string, mixed>>
     */
    private static function snapshot(Builder $query): array
    {
        return $query->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Model $model) => $model->getAttributes())->all();
    }
}
