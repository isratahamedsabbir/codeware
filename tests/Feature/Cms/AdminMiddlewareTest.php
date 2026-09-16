<?php

use App\Http\Middleware\AdminMiddleware;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Routing\Router;

it('blocks guests from admin routes', function () {
    $this->get('/admin/posts')->assertRedirect('/login');
});

it('blocks non-admin authenticated users from admin routes', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/posts')->assertForbidden();
});

it('admin middleware is registered as alias', function () {
    $middleware = app(Router::class)->getMiddleware();
    expect($middleware)->toHaveKey('admin');
    expect($middleware['admin'])->toBe(AdminMiddleware::class);
});

it('is unreachable on the vendor portal host even for an admin, keeping the two panels separate', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('http://'.config('app.vendor_host').'/admin/dashboard')
        ->assertNotFound();
});
