<?php

use App\Models\User;
use App\Models\Post;
use App\Models\Product;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use Livewire\Livewire;

test('guests and non-admins are blocked from accessing the admin dashboard', function () {
    $this->get('/admin')->assertRedirect('/login');

    $regularUser = User::factory()->create(['is_admin' => false]);
    $this->actingAs($regularUser)->get('/admin')->assertForbidden();
});

test('admin can access the admin dashboard overview page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($admin);

    // Create some sample data to check counts and rendering
    Post::factory()->count(2)->published()->create();
    Post::factory()->count(1)->draft()->create();
    Product::factory()->count(3)->create();

    $response = $this->get('/admin');
    $response->assertOk();
    $response->assertSee('System Overview');
    
    // Check Livewire rendering and state
    Livewire::test(AdminDashboard::class)
        ->assertSet('totalProducts', 3)
        ->assertSet('totalPosts', 3)
        ->assertSet('publishedPosts', 2)
        ->assertSet('draftPosts', 1)
        ->assertSee('Total Products')
        ->assertSee('Blog Posts');
});
