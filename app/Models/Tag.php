<?php

namespace App\Models;

use App\Support\Locale;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Tag extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['name', 'slug', 'status'];

    protected static function booted(): void
    {
        static::saving(function (Tag $tag) {
            if (empty($tag->slug)) {
                $name = is_array($tag->name)
                    ? ($tag->name[Locale::primary()] ?? reset($tag->name))
                    : $tag->name;
                $tag->slug = Str::slug($name);
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
