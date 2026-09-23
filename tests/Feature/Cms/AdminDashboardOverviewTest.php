<?php

use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

test('guests and non-admins are blocked from accessing the admin dashboard', function () {
    $this->get(config('app.admin_url'))->assertRedirect('/login');

    $regularUser = User::factory()->create();
    $this->actingAs($regularUser)->get(config('app.admin_url'))->assertForbidden();
});

test('admin can access the admin dashboard overview page', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    // Create some sample data to check counts and rendering
    Post::factory()->count(2)->published()->create();
    Post::factory()->count(1)->draft()->create();
    Product::factory()->count(3)->create();

    $response = $this->get(config('app.admin_url'));
    $response->assertOk();
    $response->assertSee('Welcome back,')
        ->assertSee('Workspace overview');

    // Check Livewire rendering and state
    Livewire::test(AdminDashboard::class)
        ->assertSet('totalProducts', 3)
        ->assertSet('totalPosts', 3)
        ->assertSet('publishedPosts', 2)
        ->assertSet('draftPosts', 1)
        ->assertSee('Products')
        ->assertSee('Posts');
});
