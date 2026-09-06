<?php

use App\Models\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// Setting/CmsSection cache under the test env's CACHE_STORE=array override,
// which isn't covered by RefreshDatabase's transaction rollback, so entries
// would otherwise leak between tests. Flush around every test.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(fn () => Cache::flush())
    ->afterEach(fn () => Cache::flush())
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Post/Product/ProductCategory/PostCategory have no slug column of their
 * own — Page is the only place a slug is stored, and each entity reads it
 * back through a `slug` accessor that proxies to its paired Page. So any
 * fixture that needs an entity with a specific slug has to create that
 * entity, then create (or update) its paired Page with that slug.
 */
function pairPageFor(Model $entity, string $type, string $slug, int $userId): Page
{
    $fk = match ($type) {
        'product' => 'product_id',
        'post' => 'post_id',
        default => 'category_id',
    };

    return Page::create([
        'type' => $type,
        $fk => $entity->id,
        'user_id' => $userId,
        'title' => ['en' => 'Title'],
        'slug' => $slug,
        'status' => 'active',
    ]);
}
