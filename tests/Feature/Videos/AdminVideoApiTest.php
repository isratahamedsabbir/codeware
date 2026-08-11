<?php

use App\Models\User;
use App\Models\Video;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('lists videos with pagination', function () {
    Video::factory()->count(5)->create();

    $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/videos?per_page=2')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.per_page', 2);
});

it('filters videos by status', function () {
    Video::factory()->create(['status' => 'draft']);
    Video::factory()->create(['status' => 'published']);

    $this->actingAs($this->admin)
        ->getJson('/api/v1/admin/videos?status=published')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'published');
});

it('shows a single video', function () {
    $video = Video::factory()->create();

    $this->actingAs($this->admin)
        ->getJson("/api/v1/admin/videos/{$video->id}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'title', 'youtube_link', 'thumbnail', 'status', 'sort_order']])
        ->assertJsonPath('data.id', $video->id);
});

it('creates a video', function () {
    $payload = [
        'title'        => ['en' => 'New Video', 'bn' => 'নতুন ভিডিও'],
        'youtube_link' => 'https://www.youtube.com/watch?v=test123',
        'status'       => 'published',
        'sort_order'   => 1,
    ];

    $this->actingAs($this->admin)
        ->postJson('/api/v1/admin/videos', $payload)
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id']]);

    $this->assertDatabaseHas('videos', ['status' => 'published', 'sort_order' => 1]);
});

it('updates a video', function () {
    $video = Video::factory()->create();

    $this->actingAs($this->admin)
        ->putJson("/api/v1/admin/videos/{$video->id}", [
            'title'  => ['en' => 'Updated Title', 'bn' => ''],
            'status' => 'published',
        ])
        ->assertOk();

    expect($video->fresh()->status)->toBe('published');
    expect($video->fresh()->getTranslation('title', 'en'))->toBe('Updated Title');
});

it('deletes a video', function () {
    $video = Video::factory()->create();

    $this->actingAs($this->admin)
        ->deleteJson("/api/v1/admin/videos/{$video->id}")
        ->assertNoContent();

    expect(Video::find($video->id))->toBeNull();
});

it('requires admin authentication', function () {
    $this->getJson('/api/v1/admin/videos')->assertUnauthorized();
});
