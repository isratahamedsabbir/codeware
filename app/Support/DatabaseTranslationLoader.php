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
}
