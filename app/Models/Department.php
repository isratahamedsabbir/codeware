<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

class Department extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = ['name', 'slug', 'sort_order', 'status'];

    protected static function booted(): void
    {
        static::saving(function (Department $department) {
            if (empty($department->slug)) {
                $name = is_array($department->name)
                    ? ($department->name['en'] ?? reset($department->name))
                    : $department->name;
                $department->slug = Str::slug($name);
            }
        });
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }
}
