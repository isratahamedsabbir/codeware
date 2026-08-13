<?php

use App\Livewire\Admin\Menu\Index as MenuIndex;
use App\Models\AdminMenuItem;
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
    expect(AdminMenuItem::iconExists('home'))->toBeTrue()
        ->and(AdminMenuItem::iconExists('cube'))->toBeTrue()
        ->and(AdminMenuItem::iconExists('link'))->toBeTrue()
        ->and(AdminMenuItem::iconExists('not-a-real-icon'))->toBeFalse()
        ->and(AdminMenuItem::iconExists(null))->toBeFalse()
        ->and(AdminMenuItem::iconExists('../../../etc/passwd'))->toBeFalse();
});

it('displays existing menu items', function () {
    AdminMenuItem::factory()->create(['label' => 'Reports']);

    Livewire::test(MenuIndex::class)->assertSee('Reports');
});

it('can create a standalone menu item linking to an admin page path', function () {
    Livewire::test(MenuIndex::class)
        ->call('openCreate')
        ->set('label', 'Reports')
        ->set('url', '/admin/reports')
        ->call('save');

    expect(AdminMenuItem::where('label', 'Reports')->where('url', '/admin/reports')->exists())->toBeTrue();
});

it('can create a menu item with a custom url', function () {
    Livewire::test(MenuIndex::class)
        ->call('openCreate')
        ->set('label', 'External')
        ->set('url', 'https://example.com')
        ->call('save');

    expect(AdminMenuItem::where('label', 'External')->where('url', 'https://example.com')->exists())->toBeTrue();
});

it('can create a group', function () {
    Livewire::test(MenuIndex::class)
        ->call('openCreate')
        ->set('label', 'Reports')
        ->set('is_group', true)
        ->call('save');

    expect(AdminMenuItem::where('label', 'Reports')->where('is_group', true)->exists())->toBeTrue();
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
    $item = AdminMenuItem::factory()->create(['is_active' => true]);

    Livewire::test(MenuIndex::class)->call('toggleActive', $item->id);

    expect($item->fresh()->is_active)->toBeFalse();
});

it('can reorder top-level items', function () {
    $a = AdminMenuItem::factory()->create(['sort_order' => 1]);
    $b = AdminMenuItem::factory()->create(['sort_order' => 2]);

    Livewire::test(MenuIndex::class)->call('reorderTopLevel', [$b->id, $a->id]);

    expect($a->fresh()->sort_order)->toBe(1)
        ->and($b->fresh()->sort_order)->toBe(0);
});

it('reflects a top-level reorder in the cached live sidebar immediately', function () {
    $a = AdminMenuItem::factory()->create(['label' => 'Alpha', 'sort_order' => 1]);
    $b = AdminMenuItem::factory()->create(['label' => 'Bravo', 'sort_order' => 2]);

    // Warm the cache with the original order before reordering.
    expect(AdminMenuItem::menuCached()->pluck('label')->all())->toBe(['Alpha', 'Bravo']);

    Livewire::test(MenuIndex::class)->call('reorderTopLevel', [$b->id, $a->id]);

    expect(AdminMenuItem::menuCached()->pluck('label')->all())->toBe(['Bravo', 'Alpha']);
});

it('reflects a child reorder in the cached live sidebar immediately', function () {
    $group = AdminMenuItem::factory()->group()->create(['label' => 'Group']);
    $a = AdminMenuItem::factory()->create(['label' => 'Alpha', 'parent_id' => $group->id, 'sort_order' => 1]);
    $b = AdminMenuItem::factory()->create(['label' => 'Bravo', 'parent_id' => $group->id, 'sort_order' => 2]);

    expect(AdminMenuItem::menuCached()->first()->children->pluck('label')->all())->toBe(['Alpha', 'Bravo']);

    Livewire::test(MenuIndex::class)->call('reorderChildren', $group->id, [$b->id, $a->id]);

    expect(AdminMenuItem::menuCached()->first()->children->pluck('label')->all())->toBe(['Bravo', 'Alpha']);
});

it('reflects an edited label in the cached live sidebar immediately', function () {
    $item = AdminMenuItem::factory()->create(['label' => 'Old Label']);

    expect(AdminMenuItem::menuCached()->firstWhere('id', $item->id)->label)->toBe('Old Label');

    Livewire::test(MenuIndex::class)
        ->call('edit', $item->id)
        ->set('label', 'New Label')
        ->call('save');

    expect(AdminMenuItem::menuCached()->firstWhere('id', $item->id)->label)->toBe('New Label');
});

it('can reorder children within a group', function () {
    $group = AdminMenuItem::factory()->group()->create();
    $a = AdminMenuItem::factory()->create(['parent_id' => $group->id, 'sort_order' => 1]);
    $b = AdminMenuItem::factory()->create(['parent_id' => $group->id, 'sort_order' => 2]);

    Livewire::test(MenuIndex::class)->call('reorderChildren', $group->id, [$b->id, $a->id]);

    expect($a->fresh()->sort_order)->toBe(1)
        ->and($b->fresh()->sort_order)->toBe(0);
});

it('can delete a leaf menu item', function () {
    $item = AdminMenuItem::factory()->create();

    Livewire::test(MenuIndex::class)
        ->call('confirmDelete', $item->id)
        ->call('delete');

    expect(AdminMenuItem::find($item->id))->toBeNull();
});

it('refuses to delete a group that still has children', function () {
    $group = AdminMenuItem::factory()->group()->create();
    AdminMenuItem::factory()->create(['parent_id' => $group->id]);

    Livewire::test(MenuIndex::class)
        ->call('confirmDelete', $group->id)
        ->call('delete');

    expect(AdminMenuItem::find($group->id))->not->toBeNull();
});

it('renders the seeded menu structure in the live sidebar', function () {
    $this->seed(AdminMenuSeeder::class);

    $response = $this->get('/admin/users');

    $response->assertOk();
    $response->assertSeeInOrder(['Access Control', 'Users']);
});

it('shows a route-name-backed seeded item as its resolved path when editing, and converts it to a plain link on save', function () {
    $this->seed(AdminMenuSeeder::class);
    $item = AdminMenuItem::where('route_name', 'admin.users')->firstOrFail();

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
    AdminMenuItem::where('label', 'Contacts')->update(['is_active' => false]);

    $dashboard = $this->get('/admin')->assertOk();
    $dashboard->assertDontSee('Contacts');

    Livewire::test(MenuIndex::class)->assertSee('Contacts');
});
