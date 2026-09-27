<?php

namespace App\Models;

use App\Support\Locale;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Translatable\HasTranslations;

/**
 * A content pool. Every row in the shared taxonomy table (`categories` — see
 * Category) belongs to exactly one, and the pool is what decides where a
 * category, brand or tag surfaces: product-pool rows on the storefront and in
 * the Product form, post-pool rows in the blog and the Post form.
 *
 * The two the app is built around are seeded by TypeSeeder and referenced by
 * slug from code — nothing outside the taxonomy models needs an id, so the
 * models scope themselves with subquery() rather than a resolved integer.
 */
class Type extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    public const PRODUCT = 'product';

    public const POST = 'post';

    public array $translatable = ['name'];

    protected $fillable = ['name', 'slug', 'status', 'sort_order'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Type $type) {
            // Slug auto-generates from the primary locale's name, and is left
            // alone once set — see Service and Page, which do the same. A Type
            // whose slug has to stay put because code references it by slug (see
            // PRODUCT / POST above) simply gets an explicit one written.
            if (empty($type->slug)) {
                $name = is_array($type->name)
                    ? ($type->name[Locale::primary()] ?? reset($type->name))
                    : $type->name;
                $type->slug = Slug::make((string) $name);
            } else {
                $type->slug = Slug::lower($type->slug);
            }
        });
    }

    /**
     * A ready-made subquery yielding the id of the given slug — what the taxonomy
     * models' global scopes and the admin/API validation rules that must reject
     * a row from the other pool are built from.
     *
     * Deliberately a subquery rather than a resolved integer held in a static
     * cache: a global scope is rebuilt on every query, so an eagerly-resolved
     * id would either mean a lookup per query or a memo that goes stale the
     * moment a test or a seeder creates a Type. `withTrashed()` for the same
     * reason — deactivating a Type is how you take its rows out of circulation,
     * and deleting one outright is blocked while rows still point at it, so
     * scoping must not quietly stop matching just because the row was soft
     * deleted.
     */
    public static function subquery(string $slug): Builder
    {
        return static::withTrashed()->select('id')->where('slug', $slug);
    }

    /**
     * The same id as a plain integer, for the rare place that needs a value
     * rather than a constraint — a taxonomy model's creating() hook defaulting
     * its own pool, for instance. Null when the type hasn't been created yet.
     */
    public static function idFor(string $slug): ?int
    {
        return static::withTrashed()->where('slug', $slug)->value('id');
    }

    /**
     * The `pages.type` discriminator for a category row of this type. Pages keeps
     * its own vocabulary ('product_category' / 'post_category') separate from the
     * Type slug, because a Page also types non-taxonomy entities (products,
     * posts, plain pages) that have no Type at all.
     */
    public function pageType(): string
    {
        return $this->slug === self::POST ? 'post_category' : 'product_category';
    }

    /**
     * Categories of this pool. Left unscoped by pool on purpose: this relation is
     * only ever reached from a row that already knows its own type, and the
     * ProductCategory / PostCategory models are the type-locked way to read them.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'type_id')->where('kind', Category::KIND_CATEGORY);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(ProductBrand::class, 'type_id')->where('kind', Category::KIND_BRAND);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'type_id')->where('kind', Category::KIND_TAG);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name->en');
    }

    /**
     * The rows a taxonomy form's Type dropdown offers. Not narrowed to active
     * types on purpose: a category, brand or tag already assigned to a
     * deactivated type has to keep that type as its selection, or saving the
     * form would quietly move it to whatever else happens to be listed.
     *
     * @return Collection<int, static>
     */
    public static function selectOptions(): Collection
    {
        return static::ordered()->get();
    }
}
