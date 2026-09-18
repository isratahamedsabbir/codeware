<?php

use App\Livewire\Admin\Features\Index as FeaturesIndex;
use App\Livewire\Admin\Menu\Index;
use App\Models\Feature;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use App\Support\Features;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

function disableFeature(string $key): void
{
    Feature::updateOrCreate(['key' => $key], ['label' => Features::ALL[$key], 'is_enabled' => false]);
}

it('treats every feature as enabled by default when no feature row exists', function () {
    expect(Feature::query()->exists())->toBeFalse();

    foreach (array_keys(Features::ALL) as $key) {
        expect(Features::enabled($key))->toBeTrue();
    }
});

it('disables a feature once its row is turned off', function () {
    disableFeature('blog');

    expect(Features::enabled('blog'))->toBeFalse()
        ->and(Features::enabled('products'))->toBeTrue();
});

it('blocks the routes of a disabled feature with a 404, leaving other features reachable', function () {
    disableFeature('blog');

    $this->get(route('admin.posts'))->assertNotFound();

    $this->get(route('admin.products'))->assertOk();
});

it('blocks categories and tags when the shared taxonomy feature is off, leaving blog and products reachable', function () {
    disableFeature('taxonomy');

    $this->get(route('admin.categories'))->assertNotFound();
    $this->get(route('admin.tags'))->assertNotFound();

    $this->get(route('admin.posts'))->assertOk();
    $this->get(route('admin.products'))->assertOk();
});

it('blocks chat, pages, media library, and file manager routes when their feature is off', function () {
    disableFeature('chat');
    disableFeature('pages');
    disableFeature('media-library');
    disableFeature('file-manager');

    $this->get(route('admin.chat'))->assertNotFound();
    $this->get(route('admin.pages'))->assertNotFound();
    $this->get(route('admin.media-library'))->assertNotFound();
    $this->get(route('admin.file-manager'))->assertNotFound();
});

it('blocks the discounts routes when their feature is off', function () {
    disableFeature('discounts');

    $this->get(route('admin.discounts'))->assertNotFound();
    $this->get(route('admin.discounts.create'))->assertNotFound();

    $this->get(route('admin.products'))->assertOk();
});

it('hides the Discounts link from the live sidebar once its feature is off', function () {
    $this->seed(AdminMenuSeeder::class);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Discounts');

    disableFeature('discounts');

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Discounts');
});

it('blocks localization, menu, contacts, and email templates routes when their feature is off', function () {
    disableFeature('localization');
    disableFeature('menu');
    disableFeature('contacts');
    disableFeature('email-templates');

    $this->get(route('admin.languages'))->assertNotFound();
    $this->get(route('admin.translations'))->assertNotFound();
    $this->get(route('admin.menu'))->assertNotFound();
    $this->get(route('admin.contacts'))->assertNotFound();
    $this->get(route('admin.email-templates'))->assertNotFound();

    // Settings itself is core — stays reachable even though Email Templates (in the
    // same route group) is gated separately.
    $this->get(route('admin.settings'))->assertOk();
});

it('blocks the services route when its feature is off, leaving products reachable', function () {
    disableFeature('services');

    $this->get(route('admin.services'))->assertNotFound();

    $this->get(route('admin.products'))->assertOk();
});

it('hides the Services link from the live sidebar once its feature is off', function () {
    $this->seed(AdminMenuSeeder::class);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Services');

    disableFeature('services');

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Services');
});

it('blocks the comments route when its feature is off, leaving contacts reachable', function () {
    disableFeature('comments');

    $this->get(route('admin.comments'))->assertNotFound();

    $this->get(route('admin.contacts'))->assertOk();
});

it('hides the Comments link from the live sidebar once its feature is off', function () {
    $this->seed(AdminMenuSeeder::class);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Comments');

    disableFeature('comments');

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Comments');
});

it('hides a disabled feature\'s items from the live sidebar but shows them when enabled', function () {
    $this->seed(AdminMenuSeeder::class);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Products');

    disableFeature('products');

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Attributes');
});

it('excludes a disabled feature\'s items from MenuItem::menuForCurrentUser', function () {
    $this->seed(AdminMenuSeeder::class);

    disableFeature('chat');

    $labels = MenuItem::menuForCurrentUser()
        ->flatMap(fn ($item) => $item->is_group ? $item->children->pluck('label') : collect([$item->label]))
        ->all();

    expect($labels)->not->toContain('Chat')
        ->and($labels)->toContain('Products', 'Posts');
});

it('excludes every products-feature menu item, not just the ones sharing its route prefix', function () {
    $this->seed(AdminMenuSeeder::class);

    disableFeature('products');

    $labels = MenuItem::menuForCurrentUser()
        ->flatMap(fn ($item) => $item->is_group ? $item->children->pluck('label') : collect([$item->label]))
        ->all();

    // Brands is a standalone top-level item now (not nested under Products),
    // but still shares the "products" feature gate — see MenuItem.php.
    expect($labels)->not->toContain('Attributes', 'Vendors', 'Products', 'Brands');
});

it('hides a disabled feature\'s group from the menu management screen', function () {
    $this->seed(AdminMenuSeeder::class);

    Livewire::test(Index::class)
        ->assertSee('Products')
        ->assertSee('Attributes');

    disableFeature('products');

    Livewire::test(Index::class)
        ->assertDontSee('Attributes')
        ->assertDontSee('Brands')
        ->assertDontSee('Vendors');
});

it('blocks roles, permissions, and users routes when access-control is off, leaving settings reachable', function () {
    disableFeature('access-control');

    $this->get(route('admin.roles'))->assertNotFound();
    $this->get(route('admin.permissions'))->assertNotFound();
    $this->get(route('admin.users'))->assertNotFound();

    $this->get(route('admin.settings'))->assertOk();
});

it('hides the Access Control group from the live sidebar once its feature is off', function () {
    $this->seed(AdminMenuSeeder::class);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Access Control');

    disableFeature('access-control');

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Access Control');
});

it('blocks the env route when its feature is off, leaving settings reachable', function () {
    disableFeature('env');

    $this->get(route('admin.env'))->assertNotFound();

    $this->get(route('admin.settings'))->assertOk();
});

it('hides the Env link from the live sidebar once its feature is off', function () {
    $this->seed(AdminMenuSeeder::class);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Developer Tools');

    disableFeature('env');

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Developer Tools');
});

it('blocks the audit log route when its feature is off, leaving settings reachable', function () {
    disableFeature('audit-log');

    $this->get(route('admin.history'))->assertNotFound();

    $this->get(route('admin.settings'))->assertOk();
});

it('hides the Audit Log link from the live sidebar once its feature is off', function () {
    $this->seed(AdminMenuSeeder::class);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Audit Log');

    disableFeature('audit-log');

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Audit Log');
});

it('blocks location routes when their feature is off, leaving settings reachable', function () {
    disableFeature('location');

    $this->get(route('admin.countries'))->assertNotFound();
    $this->get(route('admin.divisions'))->assertNotFound();
    $this->get(route('admin.districts'))->assertNotFound();
    $this->get(route('admin.upazilas'))->assertNotFound();

    $this->get(route('admin.settings'))->assertOk();
});

it('hides the Location group from the live sidebar once its feature is off', function () {
    $this->seed(AdminMenuSeeder::class);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Countries');

    disableFeature('location');

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Countries');
});

it('hides the CMS row action on the Pages list once the cms feature is off', function () {
    $page = Page::factory()->create();

    Livewire::test(App\Livewire\Admin\Pages\Index::class)
        ->assertSeeHtml(route('admin.cms', ['pageId' => $page->id]));

    disableFeature('cms');

    Livewire::test(App\Livewire\Admin\Pages\Index::class)
        ->assertDontSeeHtml(route('admin.cms', ['pageId' => $page->id]));
});

it('renders the features screen, only in the developer environment', function () {
    app()->instance('env', 'developer');

    Livewire::test(FeaturesIndex::class)
        ->assertSee('Blog (Posts)')
        ->assertSee('Categories & Tags (shared by Blog and Products)')
        ->assertSee('Chat')
        ->assertSee('File Manager');
});

it('404s the features route outside the developer environment', function () {
    app()->instance('env', 'production');

    $this->get(route('admin.features'))->assertNotFound();
});

it('hides the features link from the sidebar outside the developer environment', function () {
    $this->seed(AdminMenuSeeder::class);
    app()->instance('env', 'production');

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Features');
});

it('shows the features link in the sidebar in the developer environment', function () {
    $this->seed(AdminMenuSeeder::class);
    app()->instance('env', 'developer');

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Features');
});

it('can toggle a feature off through its own admin screen', function () {
    app()->instance('env', 'developer');

    Livewire::test(FeaturesIndex::class)
        ->set('features.chat', false)
        ->call('save');

    expect(Features::enabled('chat'))->toBeFalse();

    $this->get(route('admin.chat'))->assertNotFound();
});

it('reflects a disabled feature as an unchecked checkbox on reload', function () {
    app()->instance('env', 'developer');
    disableFeature('chat');

    $component = Livewire::test(FeaturesIndex::class);

    expect($component->get('features.chat'))->toBeFalse();
});

it('still enforces access-admin-system for a feature-gated route even when the feature is on', function () {
    $this->seed(RolePermissionSeeder::class);

    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)->get(route('admin.menu'))->assertForbidden();
});

it('blocks staff from the features screen even in the developer environment', function () {
    app()->instance('env', 'developer');
    $this->seed(RolePermissionSeeder::class);

    $staff = User::factory()->create();
    $staff->assignRole('staff');

    $this->actingAs($staff)->get(route('admin.features'))->assertForbidden();
});

it('allows an admin-role user to reach the features screen in the developer environment', function () {
    app()->instance('env', 'developer');
    $this->seed(RolePermissionSeeder::class);

    $adminRoleUser = User::factory()->create();
    $adminRoleUser->assignRole('admin');

    $this->actingAs($adminRoleUser)->get(route('admin.features'))->assertOk();
});

it('shows the features link in the sidebar for an admin-role user', function () {
    $this->seed(AdminMenuSeeder::class);
    $this->seed(RolePermissionSeeder::class);
    app()->instance('env', 'developer');

    $adminRoleUser = User::factory()->create();
    $adminRoleUser->assignRole('admin');

    $this->actingAs($adminRoleUser)->get(route('admin.dashboard'))->assertOk()->assertSee('Features');
});
