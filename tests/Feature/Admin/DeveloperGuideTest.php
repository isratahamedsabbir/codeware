<?php

use App\Livewire\Admin\DeveloperGuide;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('renders the developer guide for an admin', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(DeveloperGuide::class)
        ->assertOk()
        ->assertSee('Developer Guide')
        ->assertSee('Plugins')
        ->assertSee('Themes')
        ->assertSee('Features')
        ->assertSee('Settings')
        ->assertSee('Pages');
});

it('keeps the developer guide reachable for staff, like About', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)
        ->get(route('admin.developer-guide'))
        ->assertOk();
});

it('gates the developer guide behind admin access', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.developer-guide'))
        ->assertForbidden();
});

it('lists Developer Guide directly after Developer Tools, and About last', function () {
    $this->seed(AdminMenuSeeder::class);

    $system = MenuItem::where('group', 'admin-sidebar')
        ->where('label', 'Library & System')
        ->where('is_group', true)
        ->firstOrFail();

    $children = MenuItem::where('parent_id', $system->id)
        ->orderBy('sort_order')
        ->pluck('route_name')
        ->all();

    // Directly after Developer Tools, not merely somewhere in the group.
    expect(array_search('admin.env', $children, true))
        ->toBe(array_search('admin.developer-guide', $children, true) - 1);

    // About stays the final top-level row in the sidebar.
    expect(MenuItem::where('group', 'admin-sidebar')
        ->whereNull('parent_id')
        ->orderByDesc('sort_order')
        ->value('route_name'))->toBe('admin.about');
});

it('highlights code and anchors every table-of-contents link to a real section', function () {
    $html = Livewire::actingAs(User::factory()->admin()->create())
        ->test(DeveloperGuide::class)
        ->html();

    // Every anchor the contents rail links to exists as a rendered section, so
    // the two can never drift apart without a failing test.
    preg_match_all('/href="#(doc-[a-z-]+)"/', $html, $links);
    preg_match_all('/<section id="(doc-[a-z-]+)"/', $html, $sections);

    expect($links[1])->not->toBeEmpty()
        ->and(array_unique($links[1]))->toBe(array_values(array_unique($sections[1])))
        ->and($sections[1])->toHaveCount(count((new DeveloperGuide)->sections()));

    // Syntax highlighting actually ran, rather than the blocks shipping plain.
    expect($html)->toContain('doc-c-emerald', 'doc-c-violet', 'doc-c-sky');
});
