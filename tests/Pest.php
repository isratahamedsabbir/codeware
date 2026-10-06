<?php

use App\Models\Page;
use App\Support\Themes;
use App\Support\ThemeSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the pest() function to bind a different classes or traits.
|
*/

// Setting/CmsSection cache under the test env's CACHE_STORE=array override,
// which isn't covered by RefreshDatabase's transaction rollback, so entries
// would otherwise leak between tests. Flush around every test.
//
// A theme's settings live in a theme.json file in its theme folder
// (App\Support\ThemeSettings), which a database rollback cannot put back
// either — so those files are snapshotted and restored around every test too.
// Without it, a test that seeds portfolio projects would put them on disk for
// every test that ran after it, and the suite would leave the repository's own
// theme files rewritten. Cache::flush() runs either side so a restored file is
// not served from a memo written before the restore.
//
// The snapshot is held in a by-reference closure variable rather than a property
// on the test case: a dynamic property is deprecated on PHP 8.2+, and a test
// suite is the last place to be emitting deprecations.
$snapshot = null;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () use (&$snapshot) {
        Cache::flush();
        // A developer's own captcha keys in .env must not make public-form tests demand a token.
        config(['services.recaptcha.site_key' => null, 'services.recaptcha.secret_key' => null, 'services.turnstile.site_key' => null, 'services.turnstile.secret_key' => null]);
        $snapshot = themeSettingsSnapshot();
    })
    ->afterEach(function () use (&$snapshot) {
        themeSettingsRestore($snapshot ?? []);
        Cache::flush();
    })
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

/**
 * Switches on the roles RolePermissionSeeder creates switched off
 * (staff/vendor/delivery_boy/customer — see the seeder's createInactiveRole()).
 *
 * A test that exercises one of those tiers has to stand in for the admin
 * enabling the role from Admin → Roles first: an inactive role rejects its
 * holders at login and on every gated request, which is the whole point of the
 * default, so those tests would otherwise only ever prove the lockout.
 */
function activateRoles(string ...$names): void
{
    Role::whereIn('name', $names)->update(['status' => 'active']);
}

/**
 * Every installed theme's theme.json, as slug => raw file contents, or
 * `missing` for a theme that has no file.
 *
 * Read with the filesystem rather than through ThemeSettings::all(), because
 * the point is to capture the file as it is on disk — including a test that
 * replaced it with something unparseable, which the app-level reader would
 * quietly reduce to an empty map.
 *
 * @return array<string, string|null>
 */
function themeSettingsSnapshot(): array
{
    return collect(Themes::all())
        ->mapWithKeys(function (string $label, string $slug): array {
            $file = ThemeSettings::file($slug);

            return [$slug => $file !== null && is_file($file) ? (string) file_get_contents($file) : null];
        })
        ->all();
}

/**
 * Put every theme's theme.json back the way themeSettingsSnapshot() found
 * it, so one test's theme content cannot reach the next one and the repository's
 * own files are left exactly as they were.
 *
 * @param  array<string, string|null>  $snapshot
 */
function themeSettingsRestore(array $snapshot): void
{
    foreach ($snapshot as $slug => $contents) {
        $file = ThemeSettings::file($slug);

        if ($file === null) {
            continue;
        }

        if ($contents === null) {
            ThemeSettings::delete($slug);

            continue;
        }

        file_put_contents($file, $contents);
    }

    ThemeSettings::forget();
}
