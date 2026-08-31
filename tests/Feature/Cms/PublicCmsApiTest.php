<?php

use App\Models\CmsSection;
use App\Models\Page;
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
    $home = Page::factory()->create(['slug' => 'home']);
    $about = Page::factory()->create(['slug' => 'about']);
    CmsSection::factory()->create(['page_id' => $home->id, 'name' => 'hero', 'status' => 'active']);
    CmsSection::factory()->create(['page_id' => $home->id, 'name' => 'features', 'status' => 'inactive']);
    CmsSection::factory()->create(['page_id' => $about->id, 'name' => 'hero', 'status' => 'active']);

    $this->getJson('/api/v1/cms?page=home')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'hero');
});

it('excludes soft-deleted sections', function () {
    $home = Page::factory()->create(['slug' => 'home']);
    $cms = CmsSection::factory()->create(['page_id' => $home->id, 'name' => 'hero', 'status' => 'active']);
    $cms->delete();

    $this->getJson('/api/v1/cms?page=home')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('returns a single section when name is given', function () {
    $home = Page::factory()->create(['slug' => 'home']);
    CmsSection::factory()->create([
        'page_id' => $home->id,
        'name' => 'hero',
        'status' => 'active',
        'cards' => [['image' => '/storage/media/card.jpg', 'title' => 'Fast', 'description' => 'Blazing fast']],
        'metadata' => [['key' => 'og:type', 'value' => 'website'], ['key' => 'og:locale', 'value' => 'en_US']],
    ]);

    $this->getJson('/api/v1/cms?page=home&name=hero')
        ->assertOk()
        ->assertJson([
            'data' => [
                'name' => 'hero',
                'cards' => [
                    ['image' => '/storage/media/card.jpg', 'title' => 'Fast', 'description' => 'Blazing fast'],
                ],
                'metadata' => [
                    'og:type' => 'website',
                    'og:locale' => 'en_US',
                ],
            ],
        ]);
});

it('returns 404 when the page/name combination does not exist', function () {
    Page::factory()->create(['slug' => 'home']);

    $this->getJson('/api/v1/cms?page=home&name=missing')->assertNotFound();
});

it('returns 404 when the page slug does not exist', function () {
    $this->getJson('/api/v1/cms?page=missing-page')->assertNotFound();
});

it('does not return an inactive section even by exact page and name', function () {
    $home = Page::factory()->create(['slug' => 'home']);
    CmsSection::factory()->create(['page_id' => $home->id, 'name' => 'hero', 'status' => 'inactive']);

    $this->getJson('/api/v1/cms?page=home&name=hero')->assertNotFound();
});

it('caches the response in redis and serves the update immediately after a write', function () {
    $home = Page::factory()->create(['slug' => 'home']);
    $cms = CmsSection::factory()->create([
        'page_id' => $home->id,
        'name' => 'hero',
        'status' => 'active',
        'metadata' => [['key' => 'note', 'value' => 'Original value']],
    ]);

    // Prime the cache.
    $this->getJson('/api/v1/cms?page=home&name=hero')
        ->assertJsonPath('data.metadata.note', 'Original value');

    expect(Cache::store('redis')->tags(['cms'])->get('cms:home:hero')['body']['metadata']['note'])
        ->toBe('Original value');

    // A plain update — the model's saved() hook should flush the tag.
    $cms->update(['metadata' => [['key' => 'note', 'value' => 'Updated value']]]);

    expect(Cache::store('redis')->tags(['cms'])->get('cms:home:hero'))->toBeNull();

    $this->getJson('/api/v1/cms?page=home&name=hero')
        ->assertOk()
        ->assertJsonPath('data.metadata.note', 'Updated value');
});

it('refreshes the cache when a section is deleted', function () {
    $home = Page::factory()->create(['slug' => 'home']);
    $cms = CmsSection::factory()->create(['page_id' => $home->id, 'name' => 'hero', 'status' => 'active']);

    $this->getJson('/api/v1/cms?page=home')->assertJsonCount(1, 'data');

    $cms->delete();

    $this->getJson('/api/v1/cms?page=home')->assertJsonCount(0, 'data');
});
