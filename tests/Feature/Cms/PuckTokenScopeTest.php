<?php

use App\Models\User;
use App\Support\PuckEditor;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();

    // Real tokens rather than Sanctum::actingAs(), which hands the request a mock
    // whose abilities the middleware cannot read.
    $this->bearer = fn (array $abilities) => $this->withToken($this->admin->createToken('t', $abilities)->plainTextToken);
});

it('lets a Puck editor token reach the editor endpoints', function () {
    ($this->bearer)([PuckEditor::ABILITY]);

    $this->getJson('/api/v1/admin/pages')->assertOk();
});

it('refuses a Puck editor token on users, settings and contacts', function () {
    ($this->bearer)([PuckEditor::ABILITY]);

    $this->getJson('/api/v1/admin/users')->assertForbidden();
    $this->getJson('/api/v1/admin/settings')->assertForbidden();
    $this->getJson('/api/v1/admin/contacts')->assertForbidden();
    $this->getJson('/api/v1/admin/subscribers')->assertForbidden();
});

it('leaves a full-access token unchanged', function () {
    ($this->bearer)(['*']);

    $this->getJson('/api/v1/admin/users')->assertOk();
    $this->getJson('/api/v1/admin/contacts')->assertOk();
});

it('caps the Puck token lifetime however PUCK_SESSION is set', function () {
    config(['cms.puck_session_minutes' => 100000]);

    expect(PuckEditor::sessionMinutes())->toBe(PuckEditor::MAX_SESSION_MINUTES);

    config(['cms.puck_session_minutes' => 0]);

    expect(PuckEditor::sessionMinutes())->toBe(1);
});

it('requires an https editor URL in production only', function () {
    $rule = PuckEditor::baseUrlRule();
    $failed = false;
    $fail = function () use (&$failed) {
        $failed = true;
    };

    $rule('url', 'http://editor.example.com', $fail);
    expect($failed)->toBeFalse(); // not production under the test environment

    app()->detectEnvironment(fn () => 'production');

    $rule('url', 'http://editor.example.com', $fail);
    expect($failed)->toBeTrue();

    $failed = false;
    $rule('url', 'https://editor.example.com', $fail);
    expect($failed)->toBeFalse();
});
