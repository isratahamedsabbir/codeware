<?php

namespace App\Models;

use App\Concerns\CachesContent;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Tag extends Model
{
    use CachesContent, HasFactory, HasTranslations;

    // Lives in the unified taxonomy table alongside PostCategory /
    // ProductCategory / ProductBrand — the discriminator column still sets this
    // apart from those rows, but tags themselves are now further split into
    // pools: created from the Post form (post), the Product form (product), or
    // legacy rows that predate the split (tag) and stay visible in both pools.
    protected $table = 'categories';

    public const TYPE_POST = 'post';

    public const TYPE_PRODUCT = 'product';

    // Legacy rows created before tags were typed. Kept in the global scope (and
    // both form pickers) so existing data keeps working — see ProductTagsTest.
    public const TYPE_LEGACY = 'tag';

    public const TYPES = [self::TYPE_LEGACY, self::TYPE_POST, self::TYPE_PRODUCT];

    // A null type is the modern equivalent of TYPE_LEGACY — shared across both
    // pools — for rows created without picking a pool (e.g. via the API).

    public array $translatable = ['name'];

    protected $fillable = ['name', 'status', 'type'];

    /**
     * `slug` is a virtual accessor derived from the primary-locale name —
     * tags (unlike Products/ProductCategories) have no paired Page to own a
     * slug, so it's computed on the fly for the storefront's /tag/{slug}
     * links. Uses the same separator as Slug::make so it round-trips with the
     * FrontendController::resolveTag() lookup.
     */
    protected $appends = ['slug'];

    protected function slug(): Attribute
    {
        return Attribute::make(
            get: fn () => Str::slug(
                (string) ($this->getTranslation('name', Locale::primary(), false)
                    ?: $this->getTranslation('name', 'en', false)),
                '-',
            ),
        );
    }

    protected static function booted(): void
    {
        static::addGlobalScope('type', function (Builder $builder) {
            $builder->where(fn (Builder $q) => $q->whereIn('type', self::TYPES)->orWhereNull('type'));
        });

        static::saving(function (Tag $tag) {
            // Types flow in from the caller (inline creation on the Post/Product
            // forms, or the Tags admin form). A type left out entirely (or
            // blanked from a form select) still defaults to the legacy pool so
            // factories/seeders written before the split work. A type explicitly
            // set to null is left alone — that's the shared-pool state, not an
            // unset one.
            if (! array_key_exists('type', $tag->getAttributes()) || $tag->type === '') {
                $tag->type = self::TYPE_LEGACY;
            }
        });
    }

    /**
     * Both relations share the single polymorphic `taggables` pivot table
     * (tag_id/taggable_type/taggable_id) rather than a dedicated post_tag /
     * product_tag table each — see the taggables migration.
     */
    public function posts(): MorphToMany
    {
        return $this->morphedByMany(Post::class, 'taggable');
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(Product::class, 'taggable');
    }
}
