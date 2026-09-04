<?php

use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Models\Feature;
use App\Models\MenuItem;
use App\Models\User;
use App\Support\Features;
use Database\Seeders\AdminMenuSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
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
    $this->get(route('admin.post-categories'))->assertNotFound();
    $this->get(route('admin.tags'))->assertNotFound();

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

it('hides a disabled feature\'s items from the live sidebar but shows them when enabled', function () {
    $this->seed(AdminMenuSeeder::class);

    $this->get(route('admin.dashboard'))->assertOk()->assertSee('Products');

    disableFeature('products');

    $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Product Categories');
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

it('renders the features tab in settings, only in the developer environment', function () {
    app()->instance('env', 'developer');

    Livewire::test(SettingsIndex::class)
        ->assertSee('Blog (Posts, Categories, Tags)')
        ->assertSee('Chat')
        ->assertSee('File Manager');
});

it('hides the features tab outside the developer environment', function () {
    app()->instance('env', 'production');

    Livewire::test(SettingsIndex::class)
        ->assertDontSee('Blog (Posts, Categories, Tags)')
        ->assertDontSee('File Manager');
});

it('can toggle a feature off through the settings screen', function () {
    app()->instance('env', 'developer');

    Livewire::test(SettingsIndex::class)
        ->set('features.chat', false)
        ->call('save');

    expect(Features::enabled('chat'))->toBeFalse();

    $this->get(route('admin.chat'))->assertNotFound();
});

it('reflects a disabled feature as an unchecked checkbox on reload', function () {
    app()->instance('env', 'developer');
    disableFeature('chat');

    $component = Livewire::test(SettingsIndex::class);

    expect($component->get('features.chat'))->toBeFalse();
});

it('still enforces access-admin-system for a feature-gated route even when the feature is on', function () {
    $this->seed(RolePermissionSeeder::class);

    $staff = User::factory()->create(['is_admin' => false]);
    $staff->assignRole('staff');

    $this->actingAs($staff)->get(route('admin.menu'))->assertForbidden();
});
