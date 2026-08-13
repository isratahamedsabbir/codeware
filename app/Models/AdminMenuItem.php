<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AdminMenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'is_group',
        'label',
        'icon',
        'route_name',
        'url',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_group' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public static function flushCache(): void
    {
        Cache::forget('admin-menu:items');
    }

    /**
     * Whether a Flux icon by this name exists. Deliberately checks the filesystem directly
     * rather than `view()->exists('flux::icon.'.$name)` — Flux registers its icons via
     * `Blade::anonymousComponentPath()`, not `View::addNamespace()`, so the 'flux::' prefix
     * is never a real registered view namespace and `view()->exists()` always returns false
     * for it, regardless of whether the icon is valid.
     */
    public static function iconExists(?string $name): bool
    {
        if (! $name || ! preg_match('/^[a-z0-9-]+$/', $name)) {
            return false;
        }

        return file_exists(resource_path("views/flux/icon/{$name}.blade.php"))
            || file_exists(base_path("vendor/livewire/flux/stubs/resources/views/flux/icon/{$name}.blade.php"));
    }

    /**
     * Top-level menu (groups with their children attached, plus standalone links), for
     * rendering the live sidebar. Cached as plain attribute arrays and rehydrated on every
     * read — never cache Eloquent models/collections directly here, see
     * Language::activeCached() for why (unreliable unserialize() across processes with the
     * database cache driver). Groups with no active children are hidden — an empty,
     * expandable-to-nothing group is confusing in the live sidebar, though it still shows in
     * the admin management screen (which queries live, not through this cache).
     *
     * @return Collection<int, self>
     */
    public static function menuCached(): Collection
    {
        $rows = Cache::rememberForever(
            'admin-menu:items',
            fn () => static::query()->active()->ordered()->get()->toArray(),
        );

        $all = static::hydrate($rows);
        $byParent = $all->groupBy('parent_id');

        return $all->where('parent_id', null)
            ->map(fn (self $item) => tap($item, fn (self $i) => $i->setRelation(
                'children',
                $byParent->get($i->id, collect()),
            )))
            ->reject(fn (self $item) => $item->is_group && $item->children->isEmpty())
            ->values();
    }
}
