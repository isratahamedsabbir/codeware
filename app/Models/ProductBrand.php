<?php

namespace App\Models;

use App\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class ProductBrand extends Model
{
    use HasCreator, HasFactory, HasTranslations, SoftDeletes;

    // Lives in the unified taxonomy table alongside PostCategory /
    // ProductCategory / Tag — this type distinguishes brand rows.
    protected $table = 'categories';

    public array $translatable = ['name'];

    protected $fillable = ['name', 'logo', 'status', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('type', function (Builder $builder) {
            $builder->where('type', 'brand');
        });

        static::creating(function (ProductBrand $brand) {
            $brand->type = 'brand';
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'brand_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
