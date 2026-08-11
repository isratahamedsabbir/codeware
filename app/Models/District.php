<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class District extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'name', 'slug', 'sort_order', 'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (District $district) {
            if (empty($district->slug)) {
                $name = is_array($district->name)
                    ? ($district->name['en'] ?? reset($district->name))
                    : $district->name;
                $district->slug = Str::slug($name);
            }
        });
    }

    public function upazilas(): HasMany
    {
        return $this->hasMany(Upazila::class);
    }
}
