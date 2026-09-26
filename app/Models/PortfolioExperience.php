<?php

namespace App\Models;

use App\Concerns\CachesContent;
use App\Concerns\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

/**
 * A timeline entry in the portfolio theme's #experience section.
 */
class PortfolioExperience extends Model
{
    use CachesContent, HasCreator, HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['role', 'company', 'description'];

    protected $fillable = [
        'role', 'company', 'period', 'description', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
