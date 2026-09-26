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
 * A single skill card in the portfolio theme's #technology section. The cards are
 * filed under the free-text `group` column, which the theme uses to build the
 * skill-group columns.
 */
class PortfolioSkill extends Model
{
    use CachesContent, HasCreator, HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name', 'group', 'icon', 'description', 'status', 'sort_order',
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
