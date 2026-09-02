<?php

use App\Models\Setting;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->superAdmin = User::factory()->create(['is_admin' => true]);
});

it('rejects unauthenticated requests to the admin settings api', function () {
    $this->getJson('/api/v1/admin/settings')->assertUnauthorized();
});

it('rejects a plain customer account from the admin settings api', function () {
    Sanctum::actingAs(User::factory()->create(['is_admin' => false]));

    $this->getJson('/api/v1/admin/settings')->assertForbidden();
});

it('rejects a staff-role user from the admin settings api, same as the admin users api', function () {
    $staff = User::factory()->create(['is_admin' => false]);
    $staff->assignRole(Role::findOrCreate('staff', 'web'));
    Sanctum::actingAs($staff);

    $this->getJson('/api/v1/admin/settings')->assertForbidden();
    $this->putJson('/api/v1/admin/settings', ['settings' => ['site_name' => 'X']])->assertForbidden();
    $this->putJson('/api/v1/admin/layout', ['type' => 'header', 'content' => '<nav></nav>'])->assertForbidden();
});

it('allows an admin-role (non-super-admin) user into the admin settings api', function () {
    $admin = User::factory()->create(['is_admin' => false]);
    $admin->assignRole(Role::findOrCreate('admin', 'web'));
    Sanctum::actingAs($admin);

    $this->getJson('/api/v1/admin/settings')->assertOk();
});

it('lists settings with type/group/is_public metadata', function () {
    Sanctum::actingAs($this->superAdmin);
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Codeware', 'group' => 'general', 'type' => 'string', 'is_public' => true]);
    Setting::factory()->create(['key' => 'maintenance_mode', 'value' => '1', 'group' => 'general', 'type' => 'boolean', 'is_public' => false]);

    $response = $this->getJson('/api/v1/admin/settings')->assertOk();

    $response->assertJsonFragment(['key' => 'site_name', 'value' => 'Codeware', 'type' => 'string', 'group' => 'general', 'is_public' => true])
        ->assertJsonFragment(['key' => 'maintenance_mode', 'value' => true, 'type' => 'boolean', 'group' => 'general', 'is_public' => false]);
});

it('filters settings by group', function () {
    Sanctum::actingAs($this->superAdmin);
    Setting::factory()->create(['key' => 'site_name', 'group' => 'general']);
    Setting::factory()->create(['key' => 'primary_color', 'group' => 'colors']);

    $this->getJson('/api/v1/admin/settings?group=colors')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.key', 'primary_color');
});

it('bulk updates settings and busts the per-key cache', function () {
    Sanctum::actingAs($this->superAdmin);
    Setting::factory()->create(['key' => 'site_name', 'value' => 'Old Name', 'type' => 'string']);
    Setting::factory()->create(['key' => 'contact_email', 'value' => 'old@example.com', 'type' => 'string']);
    Setting::get('site_name'); // warm the cache

    $this->putJson('/api/v1/admin/settings', [
        'settings' => [
            'site_name' => 'New Name',
            'contact_email' => 'new@example.com',
        ],
    ])->assertOk()
        ->assertJsonFragment(['key' => 'site_name', 'value' => 'New Name'])
        ->assertJsonFragment(['key' => 'contact_email', 'value' => 'new@example.com']);

    expect(Setting::get('site_name'))->toBe('New Name');
});

it('stores boolean settings as "1"/"0" strings, matching the Livewire form', function () {
    Sanctum::actingAs($this->superAdmin);
    Setting::factory()->create(['key' => 'maintenance_mode', 'value' => '0', 'type' => 'boolean']);

    $this->putJson('/api/v1/admin/settings', ['settings' => ['maintenance_mode' => true]])
        ->assertOk()
        ->assertJsonFragment(['key' => 'maintenance_mode', 'value' => true]);

    expect(Setting::where('key', 'maintenance_mode')->value('value'))->toBe('1');
});

it('rejects updating a setting key that does not exist', function () {
    Sanctum::actingAs($this->superAdmin);

    $this->putJson('/api/v1/admin/settings', ['settings' => ['not_a_real_key' => 'x']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['settings']);

    expect(Setting::where('key', 'not_a_real_key')->exists())->toBeFalse();
});
