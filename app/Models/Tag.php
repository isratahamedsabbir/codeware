<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
                    ? ($tag->name['en'] ?? reset($tag->name))
                    : $tag->name;
                $tag->slug = Str::slug($name);
            }
        });
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }
}
