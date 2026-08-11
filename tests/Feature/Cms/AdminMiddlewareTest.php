<?php

use App\Models\User;

it('blocks guests from admin routes', function () {
    $this->get('/admin/posts')->assertRedirect('/login');
});

it('blocks non-admin authenticated users from admin routes', function () {
    $user = User::factory()->create(['is_admin' => false]);
    $this->actingAs($user)->get('/admin/posts')->assertForbidden();
});

it('admin middleware is registered as alias', function () {
    $middleware = app(\Illuminate\Routing\Router::class)->getMiddleware();
    expect($middleware)->toHaveKey('admin');
    expect($middleware['admin'])->toBe(\App\Http\Middleware\AdminMiddleware::class);
});
