<?php

use App\Models\User;

it('admin can create a page via store', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/admin/pages', [
            'title' => ['en' => 'New Builder Page', 'bn' => ''],
        ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'slug']]);

    expect(\App\Models\Page::where('template', 'puck')->where('status', 'inactive')->exists())->toBeTrue();
});

it('admin page store requires en title', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/admin/pages', ['title' => ['bn' => 'শিরোনাম']])
        ->assertUnprocessable();
});
