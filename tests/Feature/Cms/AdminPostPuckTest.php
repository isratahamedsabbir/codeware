<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\User;

it('admin post show includes puck_data from the paired page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $post = Post::factory()->create();
    Page::create([
        'type' => 'post', 'post_id' => $post->id, 'user_id' => $admin->id,
        'title' => ['en' => 'Title'], 'slug' => $post->slug, 'status' => 'active',
        'puck_data' => ['root' => ['props' => []], 'content' => []],
    ]);

    $this->withToken($token)
        ->getJson("/api/v1/admin/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('data.puck_data.content', []);
});

it('admin post update accepts puck_data, saving it onto the paired page', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;
    $post = Post::factory()->create();

    $puck = ['root' => ['props' => []], 'content' => [['type' => 'HeroSection', 'props' => []]]];

    $this->withToken($token)
        ->putJson("/api/v1/admin/posts/{$post->id}", ['puck_data' => $puck])
        ->assertOk()
        ->assertJsonPath('data.id', $post->id);

    $page = Page::where(['type' => 'post', 'post_id' => $post->id])->sole();
    expect($page->puck_data['content'][0]['type'])->toBe('HeroSection');
});

it('admin can create a post via store', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/admin/posts', [
            'title' => ['en' => 'Builder Post', 'bn' => ''],
        ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'slug']]);

    expect(Post::where('status', 'inactive')->exists())->toBeTrue();
});

it('admin post store requires en title', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/admin/posts', ['title' => ['bn' => 'শিরোনাম']])
        ->assertUnprocessable();
});
