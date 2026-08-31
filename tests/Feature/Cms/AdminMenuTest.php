<?php

use App\Livewire\Admin\Menu\Index as MenuIndex;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\AdminMenuSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin);
});

it('renders the menu management component', function () {
    Livewire::test(MenuIndex::class)->assertStatus(200);
});

it('checks flux icon names against the filesystem, not the unregistered flux:: view namespace', function () {
    // Regression guard: Flux registers icons via Blade::anonymousComponentPath(), not
    // View::addNamespace(), so view()->exists('flux::icon.home') is always false and must
    // never be used for this check.
    expect(MenuItem::iconExists('home'))->toBeTrue()
        ->and(MenuItem::iconExists('cube'))->toBeTrue()
        ->and(MenuItem::iconExists('link'))->toBeTrue()
        ->and(MenuItem::iconExists('not-a-real-icon'))->toBeFalse()
        ->and(MenuItem::iconExists(null))->toBeFalse()
        ->and(MenuItem::iconExists('../../../etc/passwd'))->toBeFalse();
});

it('displays existing menu items', function () {
    MenuItem::factory()->create(['label' => 'Reports']);

    Livewire::test(MenuIndex::class)->assertSee('Reports');
});

it('can create a standalone menu item linking to an admin page path', function () {
    Livewire::test(MenuIndex::class)
        ->call('openCreate')
        ->set('label', 'Reports')
        ->set('url', '/admin/reports')
        ->call('save');

    expect(MenuItem::where('label', 'Reports')->where('url', '/admin/reports')->exists())->toBeTrue();
});

it('can create a menu item with a custom url', function () {
    Livewire::test(MenuIndex::class)
        ->call('openCreate')
        ->set('label', 'External')
        ->set('url', 'https://example.com')
        ->call('save');

    expect(MenuItem::where('label', 'External')->where('url', 'https://example.com')->exists())->toBeTrue();
});

it('can create a group', function () {
    Livewire::test(MenuIndex::class)
        ->call('openCreate')
        ->set('label', 'Reports')
        ->set('is_group', true)
        ->call('save');

    expect(MenuItem::where('label', 'Reports')->where('is_group', true)->exists())->toBeTrue();
});

it('validates label is required', function () {
    Livewire::test(MenuIndex::class)
        ->call('openCreate')
        ->set('label', '')
        ->set('url', '/admin/reports')
        ->call('save')
        ->assertHasErrors(['label']);
});

it('requires a link for a non-group item', function () {
    Livewire::test(MenuIndex::class)
        ->call('openCreate')
        ->set('label', 'Broken')
        ->call('save')
        ->assertHasErrors(['url']);
});

it('rejects a link that does not start with / or http', function () {
    Livewire::test(MenuIndex::class)
        ->call('openCreate')
        ->set('label', 'Broken')
        ->set('url', 'javascript:alert(1)')
        ->call('save')
        ->assertHasErrors(['url']);
});

it('rejects an unknown icon name', function () {
    Livewire::test(MenuIndex::class)
        ->call('openCreate')
        ->set('label', 'Reports')
        ->set('url', '/admin/reports')
        ->set('icon', 'not-a-real-icon')
        ->call('save')
        ->assertHasErrors(['icon']);
});

it('can toggle a menu item active state', function () {
    $item = MenuItem::factory()->create(['is_active' => true]);

    Livewire::test(MenuIndex::class)->call('toggleActive', $item->id);

    expect($item->fresh()->is_active)->toBeFalse();
});

it('can reorder top-level items', function () {
    $a = MenuItem::factory()->create(['sort_order' => 1]);
    $b = MenuItem::factory()->create(['sort_order' => 2]);

    Livewire::test(MenuIndex::class)->call('reorderTopLevel', [$b->id, $a->id]);

    expect($a->fresh()->sort_order)->toBe(1)
        ->and($b->fresh()->sort_order)->toBe(0);
});

it('reflects a top-level reorder in the cached live sidebar immediately', function () {
    $a = MenuItem::factory()->create(['label' => 'Alpha', 'sort_order' => 1]);
    $b = MenuItem::factory()->create(['label' => 'Bravo', 'sort_order' => 2]);

    // Warm the cache with the original order before reordering.
    expect(MenuItem::menuCached()->pluck('label')->all())->toBe(['Alpha', 'Bravo']);

    Livewire::test(MenuIndex::class)->call('reorderTopLevel', [$b->id, $a->id]);

    expect(MenuItem::menuCached()->pluck('label')->all())->toBe(['Bravo', 'Alpha']);
});

it('reflects a child reorder in the cached live sidebar immediately', function () {
    $group = MenuItem::factory()->group()->create(['label' => 'Group']);
    $a = MenuItem::factory()->create(['label' => 'Alpha', 'parent_id' => $group->id, 'sort_order' => 1]);
    $b = MenuItem::factory()->create(['label' => 'Bravo', 'parent_id' => $group->id, 'sort_order' => 2]);

    expect(MenuItem::menuCached()->first()->children->pluck('label')->all())->toBe(['Alpha', 'Bravo']);

    Livewire::test(MenuIndex::class)->call('reorderChildren', $group->id, [$b->id, $a->id]);

    expect(MenuItem::menuCached()->first()->children->pluck('label')->all())->toBe(['Bravo', 'Alpha']);
});

it('reflects an edited label in the cached live sidebar immediately', function () {
    $item = MenuItem::factory()->create(['label' => 'Old Label']);

    expect(MenuItem::menuCached()->firstWhere('id', $item->id)->label)->toBe('Old Label');

    Livewire::test(MenuIndex::class)
        ->call('edit', $item->id)
        ->set('label', 'New Label')
        ->call('save');

    expect(MenuItem::menuCached()->firstWhere('id', $item->id)->label)->toBe('New Label');
});

it('can reorder children within a group', function () {
    $group = MenuItem::factory()->group()->create();
    $a = MenuItem::factory()->create(['parent_id' => $group->id, 'sort_order' => 1]);
    $b = MenuItem::factory()->create(['parent_id' => $group->id, 'sort_order' => 2]);

    Livewire::test(MenuIndex::class)->call('reorderChildren', $group->id, [$b->id, $a->id]);

    expect($a->fresh()->sort_order)->toBe(1)
        ->and($b->fresh()->sort_order)->toBe(0);
});

it('can delete a leaf menu item', function () {
    $item = MenuItem::factory()->create();

    Livewire::test(MenuIndex::class)
        ->call('confirmDelete', $item->id)
        ->call('delete');

    expect(MenuItem::find($item->id))->toBeNull();
});

it('refuses to delete a group that still has children', function () {
    $group = MenuItem::factory()->group()->create();
    MenuItem::factory()->create(['parent_id' => $group->id]);

    Livewire::test(MenuIndex::class)
        ->call('confirmDelete', $group->id)
        ->call('delete');

    expect(MenuItem::find($group->id))->not->toBeNull();
});

it('renders the seeded menu structure in the live sidebar', function () {
    $this->seed(AdminMenuSeeder::class);

    $response = $this->get('/admin/users');

    $response->assertOk();
    $response->assertSeeInOrder(['Access Control', 'Users']);
});

it('shows a route-name-backed seeded item as its resolved path when editing, and converts it to a plain link on save', function () {
    $this->seed(AdminMenuSeeder::class);
    $item = MenuItem::where('route_name', 'admin.users')->firstOrFail();

    Livewire::test(MenuIndex::class)
        ->call('edit', $item->id)
        ->assertSet('url', '/admin/users')
        ->call('save');

    $item->refresh();
    expect($item->route_name)->toBeNull()
        ->and($item->url)->toBe('/admin/users');

    // The sidebar link still resolves correctly after the conversion — admin-nav-link.blade.php
    // falls through to the raw `url` column once `route_name` is null.
    $this->get('/admin')->assertOk()->assertSee('href="/admin/users"', false);
});

it('hides an inactive item from the live sidebar but keeps it in the management list', function () {
    $this->seed(AdminMenuSeeder::class);
    MenuItem::where('label', 'Contacts')->update(['is_active' => false]);

    $dashboard = $this->get('/admin')->assertOk();
    $dashboard->assertDontSee('Contacts');

    Livewire::test(MenuIndex::class)->assertSee('Contacts');
});

it('can toggle a menu item into and out of the short menu', function () {
    $item = MenuItem::factory()->create(['is_short_menu' => false]);

    Livewire::test(MenuIndex::class)->call('toggleShortMenu', $item->id);
    expect($item->fresh()->is_short_menu)->toBeTrue();

    Livewire::test(MenuIndex::class)->call('toggleShortMenu', $item->id);
    expect($item->fresh()->is_short_menu)->toBeFalse();
});

it('only includes active, non-group, short-menu-flagged items in shortMenuCached', function () {
    $flagged = MenuItem::factory()->shortMenu()->create(['label' => 'Flagged', 'sort_order' => 1]);
    MenuItem::factory()->create(['label' => 'Unflagged', 'sort_order' => 2]);
    MenuItem::factory()->shortMenu()->inactive()->create(['label' => 'Flagged Inactive', 'sort_order' => 3]);
    $flaggedGroup = MenuItem::factory()->group()->create(['label' => 'Flagged Group', 'sort_order' => 4]);
    $flaggedGroup->forceFill(['is_short_menu' => true])->save();

    expect(MenuItem::shortMenuCached()->pluck('label')->all())->toBe(['Flagged']);
});

it('reflects a short menu toggle in the cache immediately', function () {
    $item = MenuItem::factory()->create(['label' => 'Reports']);

    expect(MenuItem::shortMenuCached()->pluck('label')->all())->toBe([]);

    Livewire::test(MenuIndex::class)->call('toggleShortMenu', $item->id);

    expect(MenuItem::shortMenuCached()->pluck('label')->all())->toBe(['Reports']);
});

it('shows the short menu dropdown in the admin top bar only when items are flagged', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(__('Short Menu'));

    MenuItem::factory()->shortMenu()->create(['label' => 'Quick Reports', 'url' => '/admin/reports']);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Quick Reports')->assertSee(__('Short Menu'));
});

it('defaults to managing the admin menu and only shows its own items', function () {
    MenuItem::factory()->create(['label' => 'Sidebar Item', 'group' => MenuItem::GROUP_ADMIN_SIDEBAR]);
    MenuItem::factory()->create(['label' => 'Frontend Item', 'group' => 'frontend-menu']);

    Livewire::test(MenuIndex::class)
        ->assertSet('activeGroup', MenuItem::GROUP_ADMIN_SIDEBAR)
        ->assertSee('Sidebar Item')
        ->assertDontSee('Frontend Item');
});

it('can create a new named menu and adds items to it, not the admin menu', function () {
    Livewire::test(MenuIndex::class)
        ->call('openNewMenu')
        ->set('newMenuLabel', 'Frontend Menu')
        ->call('createMenu')
        ->assertSet('activeGroup', 'frontend-menu')
        ->call('openCreate')
        ->set('label', 'Home')
        ->set('url', '/')
        ->call('save');

    $item = MenuItem::where('label', 'Home')->sole();

    expect($item->group)->toBe('frontend-menu');
});

it('persists a newly created menu even with zero items, so it survives navigating away', function () {
    Livewire::test(MenuIndex::class)
        ->call('openNewMenu')
        ->set('newMenuLabel', 'Frontend Menu')
        ->call('createMenu');

    expect(Menu::where('slug', 'frontend-menu')->where('name', 'Frontend Menu')->exists())->toBeTrue();

    // A brand new component instance (as if the admin reloaded the page) should
    // still list the empty menu, since it's now a real row and not just in-memory state.
    Livewire::test(MenuIndex::class)->assertSee('Frontend Menu');
});

it('rejects naming a new menu the same as the reserved admin menu', function () {
    Livewire::test(MenuIndex::class)
        ->call('openNewMenu')
        ->set('newMenuLabel', 'Admin Sidebar')
        ->call('createMenu')
        ->assertHasErrors(['newMenuLabel'])
        ->assertSet('activeGroup', MenuItem::GROUP_ADMIN_SIDEBAR);
});

it('switching menus scopes reordering and new item sort_order to that menu only', function () {
    $adminA = MenuItem::factory()->create(['group' => MenuItem::GROUP_ADMIN_SIDEBAR, 'sort_order' => 5]);
    MenuItem::factory()->create(['group' => 'frontend-menu', 'sort_order' => 99]);

    Livewire::test(MenuIndex::class)
        ->call('selectMenu', 'frontend-menu')
        ->call('openCreate')
        ->set('label', 'New Frontend Link')
        ->set('url', '/about')
        ->call('save');

    $frontendItems = MenuItem::where('group', 'frontend-menu')->pluck('sort_order', 'label');

    expect($frontendItems['New Frontend Link'])->toBe(100)
        ->and($adminA->fresh()->sort_order)->toBe(5);
});

it('lists every known menu in the selector, admin menu first', function () {
    Menu::factory()->create(['slug' => 'frontend-menu', 'name' => 'Frontend Menu']);
    MenuItem::factory()->create(['group' => 'frontend-menu']);

    Livewire::test(MenuIndex::class)
        ->assertSee('Admin Menu')
        ->assertSee('Frontend Menu');
});
