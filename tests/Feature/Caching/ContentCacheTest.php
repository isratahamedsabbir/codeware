<?php

use App\Models\Page;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Models\Voucher;
use App\Support\ContentCache;
use App\Support\Frontend;
use App\Support\Locale;
use App\Support\Themes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('orphans every key built from the old content version when busted', function () {
    expect(ContentCache::remember('probe', fn () => 'first'))->toBe('first');

    $version = ContentCache::version();

    expect(ContentCache::remember('probe', fn () => 'ignored'))->toBe('first');

    ContentCache::bust();

    expect(ContentCache::version())->toBe($version + 1)
        ->and(ContentCache::remember('probe', fn () => 'second'))->toBe('second');
});

it('serves the frontend nav pages from cache and refreshes them after a page write', function () {
    Page::factory()->published()->create(['type' => 'page', 'slug' => 'about']);

    expect(Frontend::navPages()->pluck('slug')->all())->toContain('about');

    DB::flushQueryLog();
    DB::enableQueryLog();

    Frontend::navPages();

    $pageQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query) => str_contains($query['query'], 'from "pages"'));

    DB::disableQueryLog();

    expect($pageQueries)->toBeEmpty();

    Page::factory()->published()->create(['type' => 'page', 'slug' => 'contact']);

    expect(Frontend::navPages()->pluck('slug')->all())->toContain('contact');
});

it('caches the header categories with their page relation attached', function () {
    $user = User::factory()->create();

    $category = ProductCategory::factory()->create(['name' => ['en' => 'Fertilizers', 'bn' => '']]);
    pairPageFor($category, 'product_category', 'fertilizers', $user->id);

    ProductCategory::factory()->create(['name' => ['en' => 'No Landing Page', 'bn' => '']]);

    $categories = ProductCategory::headerCached();

    expect($categories)->toHaveCount(1)
        ->and($categories->first()->name)->toBe('Fertilizers')
        ->and($categories->first()->slug)->toBe('fertilizers');

    $extra = ProductCategory::factory()->create(['name' => ['en' => 'Seeds', 'bn' => '']]);
    pairPageFor($extra, 'product_category', 'seeds', $user->id);

    expect(ProductCategory::headerCached()->pluck('slug')->all())->toContain('seeds');
});

it('caches the theme folder scan', function () {
    // Themes::forget() rather than Cache::forget(): all() also memoises the scan
    // per bootstrap, and routes/web.php asks for the theme list at boot to
    // register the theme route files — so by the time this test runs the memo is
    // already warm, and clearing only the cache key would leave all() returning
    // the memo without ever writing the entry it is meant to be asserting on.
    Themes::forget();

    expect(Themes::all())->toHaveKey('ecommerce')
        ->and(Cache::has('themes:all'))->toBeTrue();
});

it('caches the public settings payload and refreshes it when a setting is saved', function () {
    Setting::create(['key' => 'site_name', 'value' => 'Cached Site', 'group' => 'general', 'is_public' => true]);

    $this->getJson('/api/v1/settings/public')->assertJsonPath('data.general.site_name', 'Cached Site');

    expect(Cache::has('settings:public:v'.Setting::cacheVersion()))->toBeTrue();

    Setting::set('site_name', 'Updated Site');

    $this->getJson('/api/v1/settings/public')->assertJsonPath('data.general.site_name', 'Updated Site');
});

it('caches the public voucher list and refreshes it after a voucher write', function () {
    Voucher::factory()->active()->create(['name' => ['en' => 'First Gift', 'bn' => '']]);

    $this->getJson('/api/v1/vouchers')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'First Gift');

    expect(Cache::has('content:v'.ContentCache::version().':api:vouchers:'.Locale::default()))->toBeTrue();

    Voucher::factory()->active()->create(['name' => ['en' => 'Second Gift', 'bn' => '']]);

    $this->getJson('/api/v1/vouchers')->assertJsonCount(2, 'data');
});

it('caches the first page of the public services list and refreshes it after a service write', function () {
    Service::factory()->published()->create(['name' => ['en' => 'Consulting', 'bn' => '']]);

    $this->getJson('/api/v1/services')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Consulting');

    expect(Cache::has('content:v'.ContentCache::version().':api:services:'.Locale::default().':'.Setting::perPage()))->toBeTrue();

    Service::factory()->published()->create(['name' => ['en' => 'Audit', 'bn' => '']]);

    $this->getJson('/api/v1/services')->assertJsonCount(2, 'data');
});
