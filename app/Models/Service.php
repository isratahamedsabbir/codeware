<?php

namespace App\Models;

use App\Concerns\HasCreator;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Service extends Model
{
    use HasCreator, HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name', 'description', 'slug', 'price', 'featured_image', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Service $service) {
            if (empty($service->slug)) {
                $name = is_array($service->name)
                    ? ($service->name[Locale::primary()] ?? reset($service->name))
                    : $service->name;
                $service->slug = Str::slug($name);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
