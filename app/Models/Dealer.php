<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Dealer extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['name', 'address'];

    protected $fillable = [
        'name', 'slug', 'address', 'district', 'upazila',
        'phone', 'email', 'photo', 'latitude', 'longitude',
        'status', 'sort_order',
    ];

    protected $casts = [
        'latitude'   => 'decimal:7',
        'longitude'  => 'decimal:7',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Dealer $dealer) {
            if (empty($dealer->slug)) {
                $name = is_array($dealer->name)
                    ? ($dealer->name['en'] ?? reset($dealer->name))
                    : $dealer->name;
                $dealer->slug = Str::slug($name);
            }
        });
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductCategory::class,
            'dealer_product_categories'
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
