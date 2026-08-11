<?php

use App\Models\Post;

it('public post show includes puck_data when present', function () {
    $post = Post::factory()->published()->create([
        'puck_data' => [
            'root'    => ['props' => []],
            'content' => [['type' => 'HeroSection', 'props' => []]],
        ],
    ]);

    $this->getJson("/api/v1/posts/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.puck_data.content.0.type', 'HeroSection');
});

it('public post show returns null puck_data when not set', function () {
    $post = Post::factory()->published()->create(['puck_data' => null]);

    $this->getJson("/api/v1/posts/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.puck_data', null);
});
