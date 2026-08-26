<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CmsSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cms';

    protected $fillable = [
        'page',
        'section',
        'titles',
        'descriptions',
        'buttons',
        'cards',
        'images',
        'bg_image',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'titles' => 'array',
            'descriptions' => 'array',
            'buttons' => 'array',
            'cards' => 'array',
            'images' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Named `scopeOfPage`, not `scopeForPage` — Eloquent's query builder already
     * has a real `forPage($page, $perPage)` pagination helper, and Eloquent's
     * __call() checks named scopes before falling through to it, so a scope
     * literally named `forPage` silently hijacks every paginate() call.
     */
    public function scopeOfPage(Builder $query, string $page): Builder
    {
        return $query->where('page', $page);
    }
}
