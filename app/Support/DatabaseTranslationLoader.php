<?php

namespace App\Support;

use App\Models\Translation;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Wraps the framework's file loader so admin-managed rows in `translations` win over
 * whatever ships in lang/. Files stay useful as the seed and as a fallback for keys the
 * admin has not touched (or when the database is unreachable, e.g. during migrations).
 */
class DatabaseTranslationLoader implements Loader
{
    /**
     * Request-scoped memo of the distinct translation groups that actually have
     * rows per locale — lets JSON key lookups (which Laravel re-loads the
     * loader with the *key* as the group, once per key) skip the cache store
     * entirely when no admin row could ever match. Keyed by the cache
     * repository instance so it dies with the bootstrap that owns the cache.
     */
    private static array $knownGroups = [];

    /**
     * Drop the request-scoped group index so a translation write in this
     * process (admin save, seeder, test) is visible to the next load().
     */
    public static function reset(): void
    {
        self::$knownGroups = [];
    }

    public function __construct(protected Loader $inner) {}

    /**
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array
     */
    public function load($locale, $group, $namespace = null)
    {
        $fileLines = $this->inner->load($locale, $group, $namespace);

        // Vendor namespaces (e.g. __('flux::foo')) stay file-only.
        if ($namespace !== null && $namespace !== '*') {
            return $fileLines;
        }

        $dbLines = $this->fromDatabase($locale, $group);

        return $dbLines === [] ? $fileLines : array_replace($fileLines, $dbLines);
    }

    /**
     * @param  string  $namespace
     * @param  string  $hint
     * @return void
     */
    public function addNamespace($namespace, $hint)
    {
        $this->inner->addNamespace($namespace, $hint);
    }

    /**
     * @param  string  $path
     * @return void
     */
    public function addJsonPath($path)
    {
        $this->inner->addJsonPath($path);
    }

    /**
     * @return array<string, string>
     */
    public function namespaces()
    {
        return $this->inner->namespaces();
    }

    /**
     * Non-empty overrides for one locale/group, keyed by translation key.
     *
     * Blank values are skipped so an untranslated row falls through to the file value
     * instead of rendering as an empty string.
     */
    protected function fromDatabase(string $locale, string $group): array
    {
        try {
            if (! self::groupHasRows($locale, $group)) {
                return [];
            }

            return Cache::rememberForever(
                Translation::cacheKey($locale, $group),
                fn () => Schema::hasTable('translations')
                    ? Translation::query()
                        ->where('locale', $locale)
                        ->where('group', $group)
                        ->translated()
                        ->pluck('value', 'key')
                        ->all()
                    : [],
            );
        } catch (Throwable) {
            // No database yet (fresh install, migrate:fresh, cache table missing) — the
            // app must still boot and translate from files.
            return [];
        }
    }

    /**
     * Whether the locale has any stored rows for the group. An unknown group
     * (every JSON key that falls through to the loader with the key as its
     * group) is guaranteed to return [] from the query anyway, so skip the
     * cache lookup and the forever-cached empty array it would leave behind.
     */
    private static function groupHasRows(string $locale, string $group): bool
    {
        if ($group === '*') {
            return true;
        }

        $key = spl_object_id(Cache::getFacadeRoot());

        if (! array_key_exists($locale, self::$knownGroups[$key] ?? [])) {
            self::$knownGroups[$key][$locale] = Cache::rememberForever(
                "translations:groups:{$locale}",
                fn () => Schema::hasTable('translations')
                    ? Translation::query()->where('locale', $locale)->distinct()->pluck('group')->all()
                    : [],
            );
        }

        return in_array($group, self::$knownGroups[$key][$locale], true);
    }
}
