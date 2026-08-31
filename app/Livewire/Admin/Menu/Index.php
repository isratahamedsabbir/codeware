<?php

namespace App\Livewire\Admin\Menu;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Support\AdminActivity;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Index extends Component
{
    public ?int $editingId = null;

    public ?int $deletingId = null;

    #[Validate('required|string|max:191')]
    public string $label = '';

    #[Validate('nullable|string|max:64')]
    public ?string $icon = null;

    public bool $is_group = false;

    public ?string $url = null;

    public ?int $parent_id = null;

    /** Which menu (`group`) is currently being managed — see the Menu model. */
    public string $activeGroup = MenuItem::GROUP_ADMIN_SIDEBAR;

    #[Validate('required|string|max:60')]
    public string $newMenuLabel = '';

    public function selectMenu(string $group): void
    {
        $this->activeGroup = $group;
    }

    public function openNewMenu(): void
    {
        $this->reset(['newMenuLabel']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'new-menu-form');
    }

    public function createMenu(): void
    {
        $this->validateOnly('newMenuLabel');

        $name = trim($this->newMenuLabel);
        $slug = Str::slug($name, '-');

        if (blank($slug) || $slug === MenuItem::GROUP_ADMIN_SIDEBAR) {
            $this->addError('newMenuLabel', __('Please choose a different name.'));

            return;
        }

        // A real row from here on — this is what fixes an empty, just-named menu
        // vanishing the moment the admin navigates away before adding its first item.
        $menu = Menu::firstOrCreate(['slug' => $slug], ['name' => $name]);

        $this->activeGroup = $menu->slug;
        $this->newMenuLabel = '';

        $this->dispatch('close-modal', name: 'new-menu-form');
        $this->dispatch('notify', message: __('Menu created successfully'));
    }

    public function openCreate(?int $parentId = null): void
    {
        $this->reset(['editingId', 'label', 'icon', 'is_group', 'url']);
        $this->parent_id = $parentId;
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'menu-item-form');
    }

    public function edit(int $id): void
    {
        $item = MenuItem::findOrFail($id);

        $this->activeGroup = $item->group;
        $this->editingId = $item->id;
        $this->label = $item->label;
        $this->icon = $item->icon ?? '';
        $this->is_group = $item->is_group;
        // A route-name-backed item (from the original seeded menu) is shown here as its
        // resolved path so the admin edits one plain link field either way.
        $this->url = $item->route_name && Route::has($item->route_name)
            ? route($item->route_name, [], false)
            : $item->url;
        $this->parent_id = $item->parent_id;

        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'menu-item-form');
    }

    public function save(): void
    {
        $creating = $this->editingId === null;

        // is_group only takes effect when creating — an existing row's type can't flip
        // without leaving its route/url/children in an inconsistent state.
        $isGroup = $creating ? $this->is_group : MenuItem::findOrFail($this->editingId)->is_group;

        $rules = [
            'label' => 'required|string|max:191',
            'icon' => ['nullable', 'string', 'max:64', function ($attribute, $value, $fail) {
                if ($value && ! MenuItem::iconExists($value)) {
                    $fail(__('Unknown icon name.'));
                }
            }],
        ];

        if (! $isGroup) {
            $rules['url'] = ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                if ($value && ! preg_match('#^(/|https?://)#', $value)) {
                    $fail(__('Link must start with /, http:// or https://.'));
                }
            }];
            $rules['parent_id'] = ['nullable', function ($attribute, $value, $fail) {
                if ($value && ! MenuItem::where('id', $value)->where('is_group', true)->where('group', $this->activeGroup)->exists()) {
                    $fail(__('Invalid parent group.'));
                }
            }];
        }

        $this->validate($rules);

        $data = [
            'group' => $this->activeGroup,
            'label' => $this->label,
            'icon' => $this->icon ?: null,
            'is_group' => $isGroup,
            // Saving through this form always resolves to a plain link from here on,
            // even for items that started out route-name-backed (from the seeded menu).
            'route_name' => null,
            'url' => $isGroup ? null : $this->url,
            'parent_id' => $isGroup ? null : $this->parent_id,
        ];

        if ($creating) {
            $data['sort_order'] = (int) MenuItem::where('group', $this->activeGroup)->where('parent_id', $data['parent_id'])->max('sort_order') + 1;
            MenuItem::create($data);
        } else {
            // Query-builder update() doesn't fire model events, so the cache-busting
            // booted() hook never runs — flush explicitly.
            MenuItem::where('id', $this->editingId)->update($data);
            MenuItem::flushCache();
        }

        AdminActivity::log($creating ? 'created' : 'updated', "Menu item: {$this->label}");

        $this->dispatch('close-modal', name: 'menu-item-form');
        $this->dispatch('notify', message: $creating
            ? __('Menu item created successfully')
            : __('Menu item updated successfully'));
    }

    public function reorderTopLevel(array $order): void
    {
        foreach ($order as $sortOrder => $id) {
            MenuItem::where('id', $id)->where('group', $this->activeGroup)->whereNull('parent_id')->update(['sort_order' => $sortOrder]);
        }

        // Query-builder update() doesn't fire model events, so the cache-busting booted()
        // hook never runs — flush explicitly, otherwise the live sidebar keeps the old order.
        MenuItem::flushCache();
    }

    public function reorderChildren(int $parentId, array $order): void
    {
        foreach ($order as $sortOrder => $id) {
            MenuItem::where('id', $id)->where('group', $this->activeGroup)->where('parent_id', $parentId)->update(['sort_order' => $sortOrder]);
        }

        MenuItem::flushCache();
    }

    public function toggleActive(int $id): void
    {
        $item = MenuItem::findOrFail($id);
        $item->update(['is_active' => ! $item->is_active]);

        AdminActivity::log('updated', "Menu item: {$item->label} ".($item->is_active ? 'activated' : 'deactivated'));
        $this->dispatch('notify', message: __('Menu item updated successfully'));
    }

    public function toggleShortMenu(int $id): void
    {
        $item = MenuItem::findOrFail($id);
        $item->update(['is_short_menu' => ! $item->is_short_menu]);

        AdminActivity::log('updated', "Menu item: {$item->label} ".($item->is_short_menu ? 'added to' : 'removed from').' short menu');
        $this->dispatch('notify', message: __('Menu item updated successfully'));
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->dispatch('open-modal', name: 'menu-item-delete');
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            $item = MenuItem::findOrFail($this->deletingId);

            if ($item->is_group && $item->children()->exists()) {
                $this->dispatch('notify', message: __('Move or delete this group\'s items first.'));
                $this->deletingId = null;
                $this->dispatch('close-modal', name: 'menu-item-delete');

                return;
            }

            AdminActivity::log('deleted', "Menu item: {$item->label}");
            $item->delete();

            $this->dispatch('notify', message: __('Menu item deleted successfully'));
            $this->deletingId = null;
        }

        $this->dispatch('close-modal', name: 'menu-item-delete');
    }

    public function render()
    {
        $items = MenuItem::query()->where('group', $this->activeGroup)->ordered()->get();

        $topLevel = $items->where('parent_id', null)->values();
        $byParent = $items->where('parent_id', '!=', null)->groupBy('parent_id');

        $topLevel->each(fn (MenuItem $item) => $item->setRelation(
            'children',
            $byParent->get($item->id, collect())->values(),
        ));

        $groups = $topLevel->where('is_group', true)->values();

        // Admin Menu always sorts first, the rest alphabetically by name.
        $menus = Menu::all()->sortBy(fn (Menu $menu) => $menu->slug === MenuItem::GROUP_ADMIN_SIDEBAR ? '' : $menu->name)->values();

        return view('livewire.admin.menu.index', [
            'topLevel' => $topLevel,
            'menus' => $menus,
            'groups' => $groups,
        ])->layout('layouts.admin', ['title' => __('Menu')]);
    }
}
