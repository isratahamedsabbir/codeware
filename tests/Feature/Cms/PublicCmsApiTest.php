<?php

use App\Models\CmsSection;

it('requires a page query parameter', function () {
    $this->getJson('/api/v1/cms')->assertUnprocessable();
});

it('returns every active section for a page, excluding inactive ones', function () {
    CmsSection::factory()->create(['page' => 'home', 'section' => 'hero', 'status' => 'active']);
    CmsSection::factory()->create(['page' => 'home', 'section' => 'features', 'status' => 'inactive']);
    CmsSection::factory()->create(['page' => 'about', 'section' => 'hero', 'status' => 'active']);

    $this->getJson('/api/v1/cms?page=home')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.section', 'hero');
});

it('excludes soft-deleted sections', function () {
    $cms = CmsSection::factory()->create(['page' => 'home', 'section' => 'hero', 'status' => 'active']);
    $cms->delete();

    $this->getJson('/api/v1/cms?page=home')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns a single section when section is given', function () {
    CmsSection::factory()->create([
        'page' => 'home',
        'section' => 'hero',
        'status' => 'active',
        'title' => ['en' => 'Welcome', 'bn' => 'স্বাগতম'],
        'description' => ['en' => 'We build great software.', 'bn' => ''],
        'image' => '/storage/media/hero.jpg',
        'bg_image' => '/storage/media/bg.jpg',
        'buttons' => [['label' => ['en' => 'Get Started', 'bn' => ''], 'color' => '#16a34a', 'link' => '/contact']],
        'cards' => [['image' => '/storage/media/card.jpg', 'title' => ['en' => 'Fast', 'bn' => ''], 'description' => ['en' => 'Blazing fast', 'bn' => '']]],
    ]);

    $this->getJson('/api/v1/cms?page=home&section=hero')
        ->assertOk()
        ->assertJson([
            'data' => [
                'page' => 'home',
                'section' => 'hero',
                'title' => 'Welcome',
                'description' => 'We build great software.',
                'image' => '/storage/media/hero.jpg',
                'bg_image' => '/storage/media/bg.jpg',
                'buttons' => [
                    ['label' => 'Get Started', 'color' => '#16a34a', 'link' => '/contact'],
                ],
                'cards' => [
                    ['image' => '/storage/media/card.jpg', 'title' => 'Fast', 'description' => 'Blazing fast'],
                ],
            ],
        ]);
});

it('returns 404 when the page/section combination does not exist', function () {
    $this->getJson('/api/v1/cms?page=home&section=missing')->assertNotFound();
});

it('does not return an inactive section even by exact page and section', function () {
    CmsSection::factory()->create(['page' => 'home', 'section' => 'hero', 'status' => 'inactive']);

    $this->getJson('/api/v1/cms?page=home&section=hero')->assertNotFound();
});

it('resolves bn locale and falls back to en when bn is empty', function () {
    CmsSection::factory()->create([
        'page' => 'home',
        'section' => 'hero',
        'status' => 'active',
        'title' => ['en' => 'Welcome', 'bn' => 'স্বাগতম'],
        'description' => ['en' => 'English only', 'bn' => ''],
    ]);

    $this->getJson('/api/v1/cms?page=home&section=hero&locale=bn')
        ->assertOk()
        ->assertJsonPath('data.title', 'স্বাগতম')
        ->assertJsonPath('data.description', 'English only');
});
