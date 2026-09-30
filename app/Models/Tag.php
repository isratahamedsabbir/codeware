<?php

namespace App\Models;

use App\Concerns\CachesContent;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Tag extends Model
{
    use CachesContent, HasFactory, HasTranslations;

    // Lives in the unified taxonomy table alongside PostCategory /
    // ProductCategory / ProductBrand — the `kind` column is what sets this apart
    // from those rows.
    //
    // Like ProductBrand, a tag is not locked to one pool: the admin Tags screen
    // manages both and filters by `type_id`. The post/product split used to also
    // carry a third "legacy" pool for rows predating it, plus a null "shared
    // across both" state for rows created without picking a pool — both are gone,
    // since a type is now a row an admin picks and every tag belongs to exactly
    // one of them.
    protected $table = 'categories';

    public const KIND = Category::KIND_TAG;

    public array $translatable = ['name'];

    protected $fillable = ['kind', 'type_id', 'name', 'status'];

    /**
     * `slug` is a virtual accessor derived from the primary-locale name —
     * tags (unlike Products/ProductCategories) have no paired Page to own a
     * slug, so it's computed on the fly for the storefront's /tag/{slug}
     * links. Uses the same separator as Slug::make so it round-trips with the
     * App\Http\Controllers\Themes\ThemeController::resolveTag() lookup.
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
        static::addGlobalScope('kind', function (Builder $builder) {
            $builder->where('kind', self::KIND);
        });

        static::creating(function (Tag $tag) {
            $tag->kind = self::KIND;

            // A tag's pool is a choice, not a property of the model, so the admin
            // form and the inline "create tag" inputs both set type_id explicitly.
            // This is the fallback for a create() that doesn't (a seeder, a test,
            // an API caller) — type_id is NOT NULL, and Product is the same default
            // the migration gave every legacy row.
            $tag->type_id ??= Type::idFor(Type::PRODUCT);
        });
    }

    /**
     * The content pool this tag belongs to — which is what decides whether it
     * appears on the Post form's tag picker or the Product form's.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id');
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
