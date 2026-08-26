<?php

use App\Models\CmsSection;
use Illuminate\Support\Facades\Cache;

// The public CMS API caches in real Redis (tagged 'cms'), explicitly via
// Cache::store('redis') — that bypasses the test env's CACHE_STORE=array
// override and isn't covered by RefreshDatabase's transaction rollback, so
// entries would otherwise leak between tests. Flush around every test here.
beforeEach(fn () => Cache::store('redis')->tags(['cms'])->flush());
afterEach(fn () => Cache::store('redis')->tags(['cms'])->flush());

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

it('caches the response in redis and serves the update immediately after a write', function () {
    $cms = CmsSection::factory()->create([
        'page' => 'home',
        'section' => 'hero',
        'status' => 'active',
        'title' => ['en' => 'Original title', 'bn' => ''],
    ]);

    // Prime the cache.
    $this->getJson('/api/v1/cms?page=home&section=hero')
        ->assertJsonPath('data.title', 'Original title');

    expect(Cache::store('redis')->tags(['cms'])->get('cms:home:hero:en')['body']['title'])
        ->toBe('Original title');

    // A plain update — the model's saved() hook should flush the tag.
    $cms->update(['title' => ['en' => 'Updated title', 'bn' => '']]);

    expect(Cache::store('redis')->tags(['cms'])->get('cms:home:hero:en'))->toBeNull();

    $this->getJson('/api/v1/cms?page=home&section=hero')
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated title');
});

it('refreshes the cache when a section is deleted', function () {
    $cms = CmsSection::factory()->create(['page' => 'home', 'section' => 'hero', 'status' => 'active']);

    $this->getJson('/api/v1/cms?page=home')->assertJsonCount(1, 'data');

    $cms->delete();

    $this->getJson('/api/v1/cms?page=home')->assertJsonCount(0, 'data');
});
