<?php

use App\Models\Video;

it('returns only published videos on public listing', function () {
    Video::factory()->published()->create(['title' => ['en' => 'Published Video', 'bn' => '']]);
    Video::factory()->create(['title' => ['en' => 'Draft Video', 'bn' => '']]);

    $this->getJson('/api/v1/videos')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Published Video');
});

it('returns paginated videos with meta', function () {
    Video::factory()->published()->count(5)->create();

    $this->getJson('/api/v1/videos?per_page=2')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.total', 5)
        ->assertJsonPath('meta.per_page', 2);
});

it('returns video in bn locale', function () {
    Video::factory()->published()->create(['title' => ['en' => 'English Title', 'bn' => 'বাংলা শিরোনাম']]);

    $this->getJson('/api/v1/videos?locale=bn')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'বাংলা শিরোনাম');
});

it('shows a single published video', function () {
    $video = Video::factory()->published()->create();

    $this->getJson("/api/v1/videos/{$video->id}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'title', 'youtube_link', 'thumbnail', 'sort_order']])
        ->assertJsonPath('data.id', $video->id);
});

it('returns 404 for draft video on public endpoint', function () {
    $video = Video::factory()->create();

    $this->getJson("/api/v1/videos/{$video->id}")->assertNotFound();
});
