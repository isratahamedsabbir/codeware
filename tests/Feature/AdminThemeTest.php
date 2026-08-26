<?php

use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    Setting::set('site_theme', 'admin');
});

it('shows an admin login link when logged out', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('login'), false)
        ->assertSee('Admin Login');
});

it('shows a dashboard link when logged in', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get('/')
        ->assertOk()
        ->assertSee(route('admin.dashboard'), false)
        ->assertSee('Dashboard');
});
