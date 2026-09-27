<?php

namespace App\Models;

use App\Concerns\CachesContent;
use App\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;
use Spatie\Translatable\HasTranslations;

/**
 * The pool-agnostic view of category rows in the shared taxonomy table — backs
 * the single shared admin screen (App\Livewire\Admin\Categories) where an admin
 * manages Product and Post categories side by side, picking a type at creation
 * time. Everywhere else in the app (Product's category picker, Post's category
 * dropdown, public APIs) keeps using the type-locked ProductCategory /
 * PostCategory models — this model is admin-CRUD-only.
 *
 * `categories` is one table with two discriminator columns, and this is the
 * model that owns both vocabularies: `kind` says which kind of taxonomy row
 * this is (Category / ProductCategory / PostCategory all share KIND_CATEGORY),
 * and `type_id` says which content pool it belongs to (see App\Models\Type).
 */
class Category extends Model
{
    use CachesContent, HasCreator, HasFactory, HasTranslations;

    /**
     * The three kinds of row the shared taxonomy table holds. Structural rather
     * than admin-managed, unlike the pool (`type_id`) — this is what keeps
     * ProductCategory::query(), ProductBrand::query() and Tag::query() from
     * each returning every row in the table.
     */
    public const KIND_CATEGORY = 'category';

    public const KIND_TAG = 'tag';

    public const KIND_BRAND = 'brand';

    public const KINDS = [self::KIND_CATEGORY, self::KIND_TAG, self::KIND_BRAND];

    public const KIND = self::KIND_CATEGORY;

    public array $translatable = ['name', 'description'];

    protected $fillable = ['kind', 'type_id', 'parent_id', 'name', 'description', 'icon', 'sort_order', 'status', 'featured'];

    protected static function booted(): void
    {
        static::addGlobalScope('kind', function (Builder $builder) {
            $builder->where('kind', self::KIND_CATEGORY);
        });

        static::creating(function (Category $category) {
            $category->kind = self::KIND_CATEGORY;

            // type_id is NOT NULL — every row belongs to exactly one pool and
            // there is no "unassigned" state. A brand or a tag is not tied to one
            // pool by definition (its TYPE-less model has a Type dropdown instead),
            // so a create() that doesn't name one falls back to Product, which is
            // the same default the migration gave every legacy row. The pool-locked
            // subclasses (ProductCategory / PostCategory) declare their own TYPE and
            // set type_id in their own creating() hook, which runs after this one.
            if (! defined($category::class.'::TYPE')) {
                $category->type_id ??= Type::idFor(Type::PRODUCT);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
        ];
    }

    /**
     * `slug` is a virtual accessor, not a real column read directly — proxies
     * to the paired Page (see page()), same convention as ProductCategory /
     * PostCategory.
     */
    protected $appends = ['slug'];

    protected function slug(): Attribute
    {
        return Attribute::make(get: fn () => $this->page?->slug);
    }

    /**
     * The content pool this category belongs to — which is what decides whether
     * it shows up on the storefront or in the blog. Unscoped by pool, like
     * everything else here: the type-locked views are ProductCategory and
     * PostCategory.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    /**
     * Deliberately NOT constrained by the row's own pool — unlike
     * ProductCategory/PostCategory's static version of this same relation, a
     * dynamic constraint here breaks eager loading (`Category::with('page')`):
     * Eloquent builds the relation's base query once from a template instance,
     * so the constraint resolves to null there, silently matching zero pages for
     * every row. category_id alone already uniquely identifies the one paired
     * page (each id belongs to exactly one category), and the Categories form
     * rewrites that page's `type` whenever the pool changes — see its save().
     */
    public function page(): HasOne
    {
        return $this->hasOne(Page::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product', 'category_id', 'product_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    /**
     * Every category of the given type, depth-first flattened so each one is
     * immediately followed by its own children — with a `depth` property set
     * on each model for callers to indent by. Post categories don't use
     * parent_id today, so they simply come back flat (depth 0) — this still
     * works correctly for them.
     */
    public static function tree(?Collection $all = null): Collection
    {
        $all ??= static::orderBy('sort_order')->get();

        return static::flatten($all, null, 0);
    }

    private static function flatten(Collection $all, ?int $parentId, int $depth): Collection
    {
        $result = collect();

        foreach ($all->where('parent_id', $parentId) as $category) {
            $category->depth = $depth;
            $result->push($category);
            $result = $result->merge(static::flatten($all, $category->id, $depth + 1));
        }

        return $result;
    }
}
