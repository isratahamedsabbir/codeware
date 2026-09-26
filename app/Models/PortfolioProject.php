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
 * A card in the portfolio theme's #projects section.
 *
 * @property int $id
 * @property array $title
 * @property array|null $description
 * @property array|null $tech
 */
class PortfolioProject extends Model
{
    use CachesContent, HasCreator, HasFactory, HasTranslations, SoftDeletes;

    public array $translatable = ['title', 'description'];

    protected $fillable = [
        'title', 'description', 'icon', 'tech', 'stats', 'link', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'tech' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
