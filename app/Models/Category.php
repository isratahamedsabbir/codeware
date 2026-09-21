<?php

namespace App\Models;

use App\Concerns\CachesContent;
use App\Concerns\HasCreator;
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
 * The unscoped, both-types view of the `categories` table — backs the single
 * shared admin screen (App\Livewire\Admin\Categories) where an admin manages
 * Product and Post categories side by side, picking a type at creation time.
 * Everywhere else in the app (Product's category picker, Post's category
 * dropdown, public APIs) keeps using the type-locked ProductCategory /
 * PostCategory models, exactly as before — this model is admin-CRUD-only.
 */
class Category extends Model
{
    use CachesContent, HasCreator, HasFactory, HasTranslations;

    public const TYPE_PRODUCT = 'product_category';

    public const TYPE_POST = 'post_category';

    public const TYPES = [self::TYPE_PRODUCT, self::TYPE_POST];

    public array $translatable = ['name', 'description'];

    protected $fillable = ['type', 'parent_id', 'name', 'description', 'icon', 'sort_order', 'status'];

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
     * Deliberately NOT constrained by `->where('type', $this->type)` — unlike
     * ProductCategory/PostCategory's static-string version of this same
     * relation, a dynamic `$this->type` here breaks eager loading
     * (`Category::with('page')`): Eloquent builds the relation's base query
     * once from a template instance, so `$this->type` resolves to null there,
     * silently matching zero pages for every row. category_id alone already
     * uniquely identifies the one paired page (each id belongs to exactly one
     * category, whose type never disagrees with its own page's type).
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
