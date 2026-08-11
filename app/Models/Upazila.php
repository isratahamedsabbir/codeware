<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Upazila extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'district_id', 'name', 'slug', 'sort_order', 'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Upazila $upazila) {
            if (empty($upazila->slug)) {
                $name = is_array($upazila->name)
                    ? ($upazila->name['en'] ?? reset($upazila->name))
                    : $upazila->name;
                $upazila->slug = Str::slug($name);
            }
        });
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
