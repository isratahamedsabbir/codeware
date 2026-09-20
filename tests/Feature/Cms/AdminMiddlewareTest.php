<?php

use App\Http\Middleware\AdminMiddleware;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Routing\Router;

it('blocks guests from admin routes', function () {
    $this->get(config('app.admin_url').'/posts')->assertRedirect('/login');
});

it('blocks non-admin authenticated users from admin routes', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(config('app.admin_url').'/posts')->assertForbidden();
});

it('admin middleware is registered as alias', function () {
    $middleware = app(Router::class)->getMiddleware();
    expect($middleware)->toHaveKey('admin');
    expect($middleware['admin'])->toBe(AdminMiddleware::class);
});

it('keeps the admin and vendor panels on their own separate hosts', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();

    // The panels' routes are domain-bound (see bootstrap/app.php) — even an
    // admin only ever sees the admin panel on its own host, and the vendor
    // portal on its own. On the admin host the admin is at home...
    $this->actingAs($admin)->get(config('app.admin_url'))->assertOk();

    // ...while the same admin hitting the vendor host resolves only the
    // vendor portal's own access gate (can:access-vendor-portal).
    $this->actingAs($admin)->get(route('vendor.dashboard'))->assertForbidden();

    // And the old /admin path is now just a redirect into the real panel
    // host, from any host — never a 404-or-forbidden decision of its own.
    $this->actingAs($admin)
        ->get('http://'.config('app.vendor_host').'/admin/dashboard')
        ->assertRedirect(rtrim(config('app.admin_url'), '/').'/dashboard');
});
