<?php

namespace App\Models;

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

class ProductCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'categories';

    public array $translatable = ['name'];

    protected $fillable = ['type', 'parent_id', 'name', 'icon', 'sort_order', 'status'];

    /**
     * `slug` is a virtual accessor (see below), not a real column — Eloquent
     * only includes accessor-only attributes in toArray()/JSON output when
     * they're appended here, otherwise dumping the whole model silently drops
     * it (e.g. Admin API's `'category' => $post->category`).
     */
    protected $appends = ['slug'];

    protected function slug(): Attribute
    {
        return Attribute::make(get: fn () => $this->page?->slug);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('type', function (Builder $builder) {
            $builder->where('type', 'product');
        });

        static::creating(function (ProductCategory $category) {
            $category->type = 'product';
        });
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'category_product', 'category_id', 'product_id');
    }

    public function page(): HasOne
    {
        return $this->hasOne(Page::class, 'category_id')->where('type', 'product_category');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Every category, depth-first flattened so each one is immediately
     * followed by its own children — with a `depth` property set on each
     * model for callers to indent by. Pass `$all` to reuse an already-fetched
     * collection (e.g. one that's had ->withCount()/->with() applied);
     * otherwise fetches the full set itself.
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
