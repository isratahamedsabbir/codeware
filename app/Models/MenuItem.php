<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class MenuItem extends Model
{
    use HasFactory;

    /**
     * The only menu group in use today — the admin sidebar. The `group` column
     * exists so other menus (e.g. a future frontend header/footer nav) can share
     * this table later, filtered by their own group value.
     */
    public const GROUP_ADMIN_SIDEBAR = 'admin-sidebar';

    /**
     * Route name prefixes that require the 'access-admin-system' gate — kept in sync
     * with the `can:access-admin-system` route groups in routes/admin.php. Staff (see
     * RolePermissionSeeder) can enter /admin/* but not these; hiding their links from
     * the sidebar avoids dead-end clicks into a 403.
     */
    private const SYSTEM_ROUTE_PREFIXES = [
        'admin.settings',
        'admin.email-templates',
        'admin.contacts',
        'admin.roles',
        'admin.permissions',
        'admin.users',
        'admin.history',
        'admin.languages',
        'admin.translations',
        'admin.menu',
    ];

    protected $fillable = [
        'group',
        'parent_id',
        'is_group',
        'label',
        'icon',
        'route_name',
        'url',
        'sort_order',
        'is_active',
        'is_short_menu',
    ];

    protected function casts(): array
    {
        return [
            'is_group' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'is_short_menu' => 'boolean',
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
        Cache::forget('admin-menu:short-items');
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
     * rendering the live admin sidebar. Cached as plain attribute arrays and rehydrated on
     * every read — never cache Eloquent models/collections directly here, see
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
            fn () => static::query()->where('group', self::GROUP_ADMIN_SIDEBAR)->active()->ordered()->get()->toArray(),
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

    /**
     * The gate this item's route requires beyond the base access-admin check, or null
     * if it needs nothing extra (e.g. content screens, dashboard, profile).
     */
    private function requiredGate(): ?string
    {
        if (! $this->route_name) {
            return null;
        }

        if ($this->route_name === 'admin.file-manager' || str_starts_with($this->route_name, 'admin.file-manager.')) {
            return 'view-file-manager';
        }

        foreach (self::SYSTEM_ROUTE_PREFIXES as $prefix) {
            if ($this->route_name === $prefix || str_starts_with($this->route_name, $prefix.'.')) {
                return 'access-admin-system';
            }
        }

        return null;
    }

    public function isVisibleToCurrentUser(): bool
    {
        $gate = $this->requiredGate();

        return $gate === null || Gate::allows($gate);
    }

    /**
     * `menuCached()` filtered down to what the current user is actually allowed to
     * open — used for the live sidebar. Filtering happens per-request rather than
     * inside the cached query, since the underlying cache is shared across all users
     * regardless of their tier (Super Admin / Admin / Staff).
     *
     * @return Collection<int, self>
     */
    public static function menuForCurrentUser(): Collection
    {
        return static::menuCached()
            ->reject(fn (self $item) => ! $item->is_group && ! $item->isVisibleToCurrentUser())
            ->map(fn (self $item) => tap($item, function (self $i) {
                if ($i->is_group) {
                    $i->setRelation('children', $i->children->filter->isVisibleToCurrentUser()->values());
                }
            }))
            ->reject(fn (self $item) => $item->is_group && $item->children->isEmpty())
            ->values();
    }

    /**
     * Flat, cached list of admin-sidebar items flagged for the top bar's "short menu"
     * dropdown — a quick-access shortlist separate from the full sidebar. Groups are
     * excluded: a group header has no link of its own, only its children can be flagged.
     *
     * @return Collection<int, self>
     */
    public static function shortMenuCached(): Collection
    {
        $rows = Cache::rememberForever(
            'admin-menu:short-items',
            fn () => static::query()->where('group', self::GROUP_ADMIN_SIDEBAR)->active()->where('is_short_menu', true)->where('is_group', false)->ordered()->get()->toArray(),
        );

        return static::hydrate($rows);
    }
}
