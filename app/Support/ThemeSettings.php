<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * A theme's own settings, stored in the theme.json file the theme already has
 * rather than in the settings table.
 *
 * A theme is a folder the owner can download, edit, delete and re-upload
 * (see Livewire\Admin\ThemeSettings\Index::installTheme()). Its settings were
 * rows in a shared `settings` table, which meant the panel's Settings screen and
 * the Theme Settings screen were fighting over one table: a handful of themes
 * with a dozen fields each buried the site's own settings hundreds of rows deep,
 * and no theme could be handed to someone else with its content still in it.
 *
 * So the values live where the theme lives, in the manifest it already ships:
 *
 *     resources/views/frontend/themes/{slug}/theme.json
 *
 * One file per theme, not two. The manifest is the natural home for it — it is
 * the file every copy of a theme already has, it is the file a theme author
 * opens to find out what a theme is, and adding a settings.json beside it would
 * mean a theme's identity and its content could disagree about what a theme
 * needs. Themes::manifest() reads the name, description, version, author and
 * tags out of this same file and ignores everything else, so manifest fields and
 * settings fields sit side by side and neither has to move.
 *
 * That also fixes the awkward case the separate file had: theme.json is not
 * optional, so there is no "the theme's settings file is missing" state to
 * recover from in the first place. If the file is gone, the theme was not really
 * installed, and the admin screen says so instead of inviting you to recreate
 * half a theme.
 *
 * Deleting the folder takes the content with it; copying the folder carries it
 * to another site, settings and all.
 *
 * Keys keep the `theme_{slug}_*` naming they have always had rather than being
 * stripped to their bare names. Inside a file that is already scoped to one
 * theme the prefix is redundant, but it costs nothing, it keeps every theme's
 * settings.blade.php, PortfolioProfile and the storefront templates reading
 * exactly what they read before, and — the reason that decides it — a theme.json
 * opened in an editor says which theme each key belongs to, so the file is still
 * legible when it is copied out of the themes directory on its own.
 *
 * Reads are memoised per request, not cached. The file is a few kilobytes, a
 * themed page reads it once no matter how many keys it pulls out of it, and
 * deliberately not caching is what makes editing or deleting the file by hand
 * take effect on the very next request instead of after a cache flush nobody
 * remembered to run.
 */
class ThemeSettings
{
    /**
     * The file inside a theme folder that holds both its manifest and its
     * settings. Themes::manifest() has always read this name; sharing it is the
     * whole point, so it is a constant here and not a second literal in a
     * second place that could drift.
     */
    public const FILE = 'theme.json';

    /**
     * The keys in a theme's file that describe the theme rather than configure
     * it — the ones Themes::manifest() reads. Everything else in the file is a
     * setting.
     *
     * `no_index` is in here for the same reason as the rest of them: it says
     * what the theme *is* (a screen that should not be indexed), not what the
     * owner has typed into a form, so it must not turn up in the "13 values"
     * count next to the Create button.
     *
     * Spelled out here rather than taken from Themes so the dependency runs one
     * way: this class already asks Themes for a manifest, and Themes knowing
     * about this class's constants to answer that question would be a circle.
     */
    public const MANIFEST_KEYS = ['name', 'description', 'version', 'author', 'tags', 'no_index'];

    /**
     * What every settings key in a theme's file starts with, before the theme's
     * own slug. Spelled out as a constant because three separate things need to
     * agree on it: keyFor() builds keys from it, the admin screen's declaration
     * greps for it, and a theme author has to type it by hand in both
     * settings.blade.php and (if they skip the helpers) their templates.
     */
    public const PREFIX = 'theme_';

    /**
     * Per-request memos of the parsed file, keyed by theme slug and then by the
     * cache repository instance so the memo dies with the bootstrap that owns the
     * cache — per PHP-FPM request in production, per app instance in the test
     * suite, which is what stops one test's theme content leaking into the next.
     *
     * @var array<int, array<string, array<string, mixed>>>
     */
    private static array $memo = [];

    /**
     * Where a theme's settings file lives.
     *
     * Rejects anything that is not a plain folder name before it reaches the
     * filesystem. The slug comes from an installed theme folder and from the
     * admin screen's own pickers, but it is also a value that ends up
     * interpolated into a write path, so "../../config" must not be able to
     * reach one — ThemeSettings::merge() would happily create a file there.
     */
    public static function file(string $slug): ?string
    {
        if (! static::isValidSlug($slug)) {
            return null;
        }

        return Themes::path().'/'.$slug.'/'.self::FILE;
    }

    /**
     * A usable theme folder name: the same shape the installer slugifies an
     * uploaded zip to, and the same shape Themes::all() reads off the disk.
     */
    public static function isValidSlug(string $slug): bool
    {
        return $slug !== '' && preg_match('/^[A-Za-z0-9_-]+$/', $slug) === 1;
    }

    public static function exists(string $slug): bool
    {
        $file = static::file($slug);

        return $file !== null && is_file($file);
    }

    /**
     * Every value a theme has stored, as key => value. An absent, empty or
     * unparseable file reads as no values at all rather than as an error: a
     * theme whose folder has no theme.json is a theme with nothing configured
     * yet, which is exactly the state the admin's "Create theme.json" button
     * exists to move out of.
     *
     * A file holding a JSON list or a JSON scalar is treated the same way. The
     * owner is expected to be able to open this file in an editor, so whatever
     * they put in it must not be able to take the storefront down.
     *
     * The manifest's own fields come back too, since they are in the same file.
     * Nothing reads them from here — Themes::manifest() is the reader for those,
     * with the fallbacks a bare key cannot have — but returning the whole file
     * rather than a filtered copy is what lets merge() write a theme's settings
     * without quietly dropping the name off its own manifest.
     *
     * @return array<string, mixed>
     */
    public static function all(?string $slug = null): array
    {
        $slug ??= Themes::active();
        $file = static::file($slug);

        if ($file === null) {
            return [];
        }

        $memoKey = static::memoKey();
        $present = is_file($file);

        // Keyed on the file's mtime and size as well as the slug, so a file
        // replaced from outside the app (an editor, a deploy, a git checkout) is
        // re-read within the same second rather than served from a memo written
        // before it. The leading flag is what tells a zero-byte file apart from
        // no file at all — both report a zero stamp on a filesystem with coarse
        // timestamps, and only one of them is a file the owner can edit.
        $stamp = ($present ? 'file' : 'none')
            .':'.($present ? (int) filemtime($file) : 0)
            .':'.($present ? (int) filesize($file) : 0);

        if ((static::$memo[$memoKey][$slug]['stamp'] ?? null) === $stamp) {
            return static::$memo[$memoKey][$slug]['values'];
        }

        $values = [];

        if ($present && is_readable($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);

            // A hand-written file that is a non-empty list rather than a
            // key => value map has no keys to look anything up by, so there is
            // nothing to read. An empty {} is both, and is just no values.
            if (is_array($decoded) && ($decoded === [] || ! array_is_list($decoded))) {
                $values = $decoded;
            }
        }

        // Assigned, then returned separately: `return $memo[...] = [...]` returns
        // the memo *entry* on a miss, so the first read of a file in a request
        // would hand callers a ['stamp' => …, 'values' => …] map — which
        // merge() would then write straight back into the file.
        static::$memo[$memoKey][$slug] = ['stamp' => $stamp, 'values' => $values];

        return $values;
    }

    /**
     * Just the settings in a theme's file, with the manifest's own fields left
     * out — the file's contents minus the handful of keys that describe the theme
     * instead of configuring it.
     *
     * Reads go through all() and look up one key, so they were never going to trip
     * over a manifest. This is for talking about the file as a whole: "13 values"
     * next to the Create button, which would otherwise count "name" and "version"
     * as things the owner configured.
     *
     * @return array<string, mixed>
     */
    public static function settings(?string $slug = null): array
    {
        return array_diff_key(static::all($slug), array_flip(static::MANIFEST_KEYS));
    }

    /**
     * The key a field is actually stored under, from either form a caller may
     * have to hand.
     *
     * A theme's settings live in that theme's own file under `theme_{slug}_*`
     * keys, but nobody should have to repeat the theme's own name on every read
     * of its own template — a portfolio template asking for "hero_title" is
     * already unambiguous about which theme it is. So a bare field name is
     * resolved to the active theme's own prefixed key, and a key that already
     * carries the prefix is passed through untouched.
     *
     * Both forms therefore work everywhere, which is what lets a new theme be
     * written in the short form and an existing theme keep the long one without
     * either being wrong:
     *
     *     ThemeSettings::text('portfolio', 'hero_title')            // short
     *     ThemeSettings::text('portfolio', 'theme_portfolio_hero_title')  // long
     *
     * The prefix is never rewritten once present: a key that says which theme it
     * belongs to is taken at its word, so reading one theme's value out of
     * another's file is a miss rather than a silent cross-theme read.
     */
    public static function keyFor(string $field, ?string $slug = null): string
    {
        $field = trim($field);

        if ($field === '' || str_starts_with($field, self::PREFIX)) {
            return $field;
        }

        return self::PREFIX.($slug ?? Themes::active()).'_'.$field;
    }

    /**
     * One stored value, or $default when the theme has no file, no such key, or
     * a null in that key. A null in the file counts as absent on purpose: it is
     * what a half-finished hand edit leaves behind, and callers asking for a
     * string should get their default rather than a null they have to guard.
     *
     * A key that is *present and blank* is not absent: it is there, holding an
     * empty string, and it comes back as one. That is the honest answer — a
     * theme's file carries a key for every field its form declares, so "the owner
     * left this blank" and "this theme has no such field" are different states
     * and a caller that wants a fallback has to say so (`: $color('a') ?:
     * $color('b')` rather than `??`).
     */
    public static function get(?string $slug, string $key, mixed $default = null): mixed
    {
        $values = static::all($slug);

        return ($values[static::keyFor($key, $slug)] ?? null) ?? $default;
    }

    /**
     * A stored value as a trimmed string — the shape every text field, colour
     * and URL in a theme's settings form is read as.
     */
    public static function text(?string $slug, string $key, string $default = ''): string
    {
        $value = static::get($slug, $key);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /**
     * A stored list of rows — the read side of a <x-admin-repeatable-fields>
     * declaration, as a plain list of flat string maps.
     *
     * The file stores these as real JSON arrays, but a value that is still a
     * JSON *string* is decoded rather than discarded. That is not nostalgia for
     * the settings table: it is the shape a copy-paste out of an old database
     * row, or a hand edit that pasted the value without unwrapping it, arrives
     * in — and a theme whose project list silently vanished because the owner
     * pasted it slightly wrong is the worst possible outcome here.
     *
     * Fields whose value is not a scalar are dropped rather than passed on, as
     * is a row that is not an object at all — except when the caller says which
     * field a bare string stands for.
     *
     * $identity is that answer, and it is the caller's to give because only the
     * caller knows it: the portfolio's lists are keyed on "title", "role" and
     * "quote" as easily as on "value". Pass it to keep a hand-edited list of
     * bare strings readable as one-field rows instead of silently losing them
     * (["Freelance", "Contract"] in an education list is a real paste, not a
     * corruption); leave it out to take strictly what the file says.
     *
     * @return array<int, array<string, string>>
     */
    public static function rows(?string $slug, string $key, ?string $identity = null): array
    {
        $value = static::get($slug, $key);

        if (is_string($value)) {
            $value = json_decode(trim($value), true);
        }

        if (! is_array($value) || ! array_is_list($value)) {
            return [];
        }

        $rows = [];

        foreach ($value as $row) {
            if (! is_array($row)) {
                // A string, specifically: a bare number or null in a list of
                // titles is not a half-finished paste, it is junk, and reading it
                // as a row would print "42" on the public page.
                if ($identity !== null && is_string($row)) {
                    $row = [$identity => $row];
                } else {
                    continue;
                }
            }

            $flat = [];

            foreach ($row as $field => $cell) {
                if (is_string($field) && is_scalar($cell)) {
                    $flat[$field] = trim((string) $cell);
                }
            }

            $rows[] = $flat;
        }

        return $rows;
    }

    /**
     * Write values into a theme's file, leaving every other key in it alone.
     *
     * Merging rather than replacing is the whole reason this is safe to call
     * with only the fields a form happens to show: a key the owner added by hand,
     * or one belonging to a section that has not been built yet, survives every
     * save. The manifest fields survive it too, for free — that is the same rule
     * applied to the same file. Returns false when there was nothing to write, or
     * when the theme folder is not one this app can write to.
     */
    public static function merge(string $slug, array $values): bool
    {
        if ($values === [] || ! static::isValidSlug($slug)) {
            return false;
        }

        return static::write($slug, array_merge(static::all($slug), $values));
    }

    /**
     * Create a theme's file, seeded with a manifest and whatever values it is
     * given. The admin screen's "Create theme.json" button: theme.json is part of
     * the theme folder, so an owner who tidies up, re-installs a theme or checks
     * out an old revision can leave without it — and without it the theme is
     * nameless, unversioned and has nowhere to keep the settings they entered.
     *
     * Seeded from Themes::manifest()'s own fallbacks, so what lands on disk is a
     * well-formed theme.json rather than a settings map wearing a manifest's
     * name. A name the owner had already chosen is not lost, because this refuses
     * to touch a file that is already there: this is the recovery path, not a
     * reset, and a create that overwrote would be a single click away from
     * wiping a finished theme's manifest and content. It returns false so the
     * caller can say so rather than appear to have succeeded.
     */
    public static function create(string $slug, array $values = []): bool
    {
        if (static::exists($slug)) {
            return false;
        }

        return static::write($slug, array_merge(Themes::manifest($slug), $values));
    }

    /**
     * Remove a theme's file outright, manifest and all.
     *
     * Not a "clear these settings" operation — it takes the theme's name,
     * description and version with the values, so it belongs to cleanup code
     * (uninstalling a theme, a test restoring the disk to how it found it) and
     * not to the admin screen. Clearing settings is merge() with empty lists.
     */
    public static function delete(string $slug): bool
    {
        $file = static::file($slug);

        if ($file === null || ! is_file($file)) {
            return false;
        }

        return File::delete($file);
    }

    /**
     * Write the file, pretty-printed so an owner editing it by hand can read it.
     *
     * Slashes and unicode are left unescaped for the same reason: this file is
     * meant to be opened in an editor, and "https:\/\/example.com" or
     * "Bengali" turned into escapes is a file nobody wants to touch by hand.
     * The write is atomic through a rename, so a failed request cannot leave a
     * half-written file that reads as valid JSON on the way in and as truncated
     * on the way out.
     */
    private static function write(string $slug, array $values): bool
    {
        $file = static::file($slug);

        if ($file === null) {
            return false;
        }

        $directory = dirname($file);

        // The theme folder has to be there already. This class describes themes
        // that are installed, so a folder appearing from under it would be a
        // theme the app never installed — and Themes::all() would list it, and
        // with it a theme.json nobody ever chose to create. It is also the
        // difference between a typo'd slug failing and a typo'd slug quietly
        // becoming a new theme.
        if (! File::isDirectory($directory)) {
            return false;
        }

        $json = json_encode((object) $values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // json_encode() returns false for anything it cannot represent. A
        // settings file that is not valid JSON is a storefront that cannot be
        // configured, so this is a hard failure rather than a partial write.
        if ($json === false) {
            return false;
        }

        $temp = $file.'.'.uniqid('', true).'.tmp';

        if (file_put_contents($temp, $json."\n", LOCK_EX) === false) {
            return false;
        }

        if (! @rename($temp, $file)) {
            File::delete($temp);

            return false;
        }

        // Drop the memo rather than updating it: the new mtime is not knowable
        // to the second on every filesystem, and a stale memo here would show
        // the owner yesterday's values on the screen they just saved.
        static::forget($slug);

        return true;
    }

    /**
     * Forget one theme's memo, or every theme's. Called after a write, and
     * available to anything that replaced a file from outside the app.
     *
     * Cleared across every memo key rather than just the current one, because a
     * key is an object id: when the bootstrap that owned it is torn down — a new
     * app per test, a worker handling the next job — PHP hands that id to the
     * next object, and a later request would find the dead bootstrap's memo
     * sitting under its own key. A memo outliving the request that filled it is
     * the one way a read here can disagree with the file in front of it.
     */
    public static function forget(?string $slug = null): void
    {
        foreach (array_keys(static::$memo) as $memoKey) {
            if ($slug === null) {
                unset(static::$memo[$memoKey]);

                continue;
            }

            unset(static::$memo[$memoKey][$slug]);
        }
    }

    /**
     * The cache repository this bootstrap owns, as a memo key — unique per
     * PHP-FPM request and per app instance in the test suite. Same idea as
     * Setting's own memos, and for the same reason.
     */
    private static function memoKey(): int
    {
        return spl_object_id(Cache::getFacadeRoot());
    }
}
