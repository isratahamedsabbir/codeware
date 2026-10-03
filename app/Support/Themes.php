<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Themes
{
    /**
     * Every storefront route name mapped to the template its page renders
     * through — the mirror image of the `$this->view('...')` call in each themed
     * controller (see App\Http\Controllers\Themes\ThemeController::view()).
     * Two things depend on this staying in sync: the 404 a missing template
     * produces, and canRenderLink(), which drops a nav/menu link pointing at a
     * page the active theme ships no template for rather than leaving a dead end.
     *
     * Deliberately keyed by route *name*, not URL, and resolved from a
     * MenuItem's stored URL at runtime (see routeNameFor()) rather than parsed
     * by hand here — the menu admin form stores plain URLs, so the router is
     * the only thing that knows which route a stored URL means.
     *
     * A name absent from this map isn't a themed storefront page at all (the
     * customer login, /admin's legacy bounce, an API path) and is always kept.
     */
    private const ROUTE_TEMPLATES = [
        'home' => 'home',
        'page' => 'page',
        'shop' => 'shop',
        'products.show' => 'product',
        'shop.category' => 'category',
        'shop.brand' => 'brand',
        'shop.tag' => 'tag',
        'favorites' => 'favorites',
        'blog' => 'blog',
        'blog.post' => 'post',
        'cart' => 'cart',
        'checkout' => 'checkout',
        'checkout.confirmation' => 'order-confirmation',
        'account.dashboard' => 'account/dashboard',
        'account.orders' => 'account/orders',
        'account.orders.show' => 'account/order',
        'account.profile' => 'account/profile',
    ];

    /**
     * Request-scoped memo of the cached folder scan — active()/view() are hit
     * several times a themed page. Keyed by the cache repository instance so
     * the memo dies with the bootstrap that owns the cache.
     */
    private static array $all = [];

    /**
     * Request-scoped memo of manifest(), keyed by cache repository instance and
     * then by slug — the same pattern as $all, but invalidated by the file's
     * mtime and size rather than held for the whole request unconditionally.
     * manifest() is called mid-request by code that writes the very file it
     * reads (ThemeSettings::create() reads it to seed a new theme.json, the
     * theme installer seeds a freshly moved-in folder), so a memo that could
     * not tell "the file changed under me" from "cache this forever" would
     * hand a newly-installed theme back its own stale defaults.
     *
     * @var array<int, array<string, array{stamp: string, manifest: array}>>
     */
    private static array $manifests = [];

    /**
     * Where the themes live — themes/{slug}/, one self-contained module per theme,
     * the same shape as plugins/{slug}/. Everything a theme is made of is inside
     * its own folder, so it can be zipped up and handed to someone else whole:
     *
     *     themes/{slug}/
     *         theme.json          manifest + settings values
     *         settings.blade.php  its admin settings screen
     *         routes/web.php      its storefront routes
     *         *.blade.php         its templates (partials/, errors/, account/ ...)
     *         Controllers/        namespace Themes\{Slug}\Controllers
     *         database/           migrations/ and seeders/ (Themes\{Slug}\Database\Seeders)
     *         public/             everything the web serves, at /themes/{slug}/...
     *             css/theme.css     its own Vite entry
     *             css/, js/, img/   hand-written static files, served as-is
     */
    public static function path(): string
    {
        return base_path('themes');
    }

    /**
     * The route name => template map above, exposed so the two things that have
     * to agree with it can be checked: the theme route files that register those
     * names, and the fact that every one of them is a page some theme serves.
     */
    public static function routeTemplates(): array
    {
        return self::ROUTE_TEMPLATES;
    }

    /**
     * The view namespace a theme's templates are registered under, so a template
     * is named theme-{slug}::home or theme-{slug}::partials.header whatever
     * directory the folder happens to live in.
     */
    public static function viewNamespace(string $slug): string
    {
        return 'theme-'.$slug;
    }

    /**
     * Point the theme-{slug}:: view namespace at the theme's folder. Idempotent;
     * ThemeServiceProvider does it for every installed theme at boot, and the
     * code that creates or installs a theme calls it for the new one, since the
     * provider has already booted by then.
     */
    public static function registerViews(string $slug): void
    {
        View::addNamespace(static::viewNamespace($slug), static::path().'/'.$slug);
    }

    /**
     * The folder whose files are served publicly at /themes/{slug}/... — the only
     * part of a theme the web can reach directly (see ThemeAssetController).
     */
    public static function publicPath(string $theme): string
    {
        return static::path().'/'.$theme.'/public';
    }

    /**
     * A theme's database folder — migrations/ is loaded by ThemeServiceProvider
     * and seeders/ is autoloaded as Themes\{Slug}\Database\Seeders.
     */
    public static function databasePath(string $theme): string
    {
        return static::path().'/'.$theme.'/database';
    }

    /**
     * The stylesheet a theme ships — themes/{slug}/public/css/theme.css — or null
     * when it ships none.
     */
    public static function stylesheet(string $theme): ?string
    {
        $file = static::path().'/'.$theme.'/public/css/theme.css';

        return is_file($file) ? $file : null;
    }

    /**
     * The route file routes/web.php registers for a theme —
     * themes/{slug}/routes/web.php — or null when it has none.
     */
    public static function routeFile(string $theme): ?string
    {
        $file = static::path().'/'.$theme.'/routes/web.php';

        return is_file($file) ? $file : null;
    }

    /**
     * Resolve a class in the Themes\{Studly}\... namespace to a file inside the
     * matching theme folder, so a theme's controllers and seeders load without a
     * composer dump — which a theme installed from a zip could never wait for.
     * Themes\Ecommerce\Controllers\ShopController is
     * themes/ecommerce/Controllers/ShopController.php, and
     * Themes\Portfolio\Database\Seeders\MenuSeeder is
     * themes/portfolio/database/seeders/MenuSeeder.php (directories are tried as
     * written and then lowercased).
     *
     * Registered by ThemeServiceProvider. Reads the folder directly rather than
     * through all(), which needs the cache and so is not safe to call from inside
     * an autoloader.
     */
    public static function autoload(string $class): void
    {
        if (! str_starts_with($class, 'Themes\\')) {
            return;
        }

        $parts = explode('\\', $class);

        if (count($parts) < 3) {
            return;
        }

        $slug = static::slugForStudly($parts[1]);

        if ($slug === null) {
            return;
        }

        $name = array_pop($parts);
        $directories = array_slice($parts, 2);
        $base = static::path().'/'.$slug.'/';

        foreach ([implode('/', $directories), strtolower(implode('/', $directories))] as $directory) {
            $file = $base.($directory === '' ? '' : $directory.'/').$name.'.php';

            if (is_file($file)) {
                require_once $file;

                return;
            }
        }
    }

    private static function slugForStudly(string $studly): ?string
    {
        if (! is_dir(static::path())) {
            return null;
        }

        foreach (scandir(static::path()) as $entry) {
            if ($entry[0] !== '.' && is_dir(static::path().'/'.$entry) && Str::studly($entry) === $studly) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Whether a given theme ships a stylesheet of its own. A theme without one
     * is served the catch-all storefront bundle instead, which scans every
     * theme's templates — so it is styled, just at the price of carrying every
     * other theme's classes too. See storefrontEntry() for which of the two a
     * given theme gets.
     */
    public static function hasStylesheet(string $theme): bool
    {
        return static::stylesheet($theme) !== null;
    }

    /**
     * The Vite entry the active theme's pages load their stylesheet from: its own
     * when it ships one, the catch-all storefront bundle when it does not.
     *
     * This is the stylesheet half of the same rule the rest of this class applies
     * to templates and routes — a theme is served by what it ships, and a
     * fallback to something another theme made is only ever the *styling* of a
     * page, never its content or its route. The deliberate exception is a theme
     * that ships no stylesheet at all, which is styled by the catch-all rather
     * than left unstyled: bare HTML is a broken storefront, and a theme
     * installed later as a zip is exactly that case.
     */
    public static function storefrontEntry(?string $theme = null): string
    {
        $theme ??= static::active();

        return static::hasStylesheet($theme)
            ? 'themes/'.$theme.'/public/css/theme.css'
            : 'resources/css/storefront.css';
    }

    /**
     * Whether a given theme ships a route file at all. A theme without one
     * simply contributes no routes — the same rule as a template it doesn't
     * ship — so its public surface is whatever routes/web.php gives every theme.
     */
    public static function routeFileExists(string $theme): bool
    {
        return static::routeFile($theme) !== null;
    }

    /**
     * Every theme's route file that exists, as slug => absolute path. All of them
     * are registered by routes/web.php; the 'theme:{slug}' middleware each one is
     * wrapped in is what makes only the active theme's answer (see
     * App\Http\Middleware\EnsureActiveTheme).
     *
     * @return array<string, string>
     */
    public static function allRouteFiles(): array
    {
        return collect(static::all())
            ->keys()
            ->filter(fn (string $slug) => static::routeFileExists($slug))
            ->mapWithKeys(fn (string $slug) => [$slug => (string) static::routeFile($slug)])
            ->all();
    }

    /**
     * Every theme folder under themes/, as slug => label.
     *
     * The folder list is a filesystem scan (scandir + is_dir per entry), so it's
     * cached for a day rather than repeated on every themed request — the admin
     * re-reads it live inside its own picker cache, and the folder list almost
     * never changes outside a deployment.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        $key = spl_object_id(Cache::getFacadeRoot());

        if (! array_key_exists($key, self::$all)) {
            try {
                self::$all[$key] = Cache::remember('themes:all', 86400, fn () => self::scan());
            } catch (Throwable) {
                // all() is reached from routes/web.php while the router boots,
                // which migrate:fresh does after dropping every table — with a
                // database cache store that is a missing `cache` table. The
                // folder list is a filesystem scan either way, so serve it
                // uncached rather than failing the whole command.
                self::$all[$key] = self::scan();
            }
        }

        return self::$all[$key];
    }

    /**
     * @return array<string, string>
     */
    private static function scan(): array
    {
        if (! is_dir(self::path())) {
            return [];
        }

        return collect(scandir(self::path()))
            ->filter(fn ($entry) => ! in_array($entry, ['.', '..'], true) && is_dir(self::path().'/'.$entry))
            ->sort()
            ->mapWithKeys(fn ($slug) => [$slug => ucwords(str_replace(['-', '_'], ' ', $slug))])
            ->all();
    }

    /**
     * Which installed theme already answers to a name, or null when the name is
     * free — the uniqueness check's reporting half. Being told *which* theme is
     * the one already called "Shop" is the difference between a message the
     * owner can act on and one they have to go and work out.
     *
     * $excludeSlug is the theme being edited, so a theme keeping its own name is
     * not a collision with itself. It is the same exclusion the admin form needs
     * when it is about to write a name back over the theme it is showing.
     *
     * @see static::isNameUnique() for the yes/no half
     */
    public static function themeWithName(string $name, ?string $excludeSlug = null): ?string
    {
        $needle = static::nameKey($name);

        if ($needle === '') {
            return null;
        }

        foreach (array_keys(static::all()) as $slug) {
            if ($slug === $excludeSlug) {
                continue;
            }

            if (static::nameKey((string) static::manifest($slug)['name']) === $needle) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Whether a theme name is free, by the same comparison themeWithName() makes.
     *
     * An empty name is *not* free. A theme.json with a blank "name" is read back
     * as the slug in title case (see manifest()'s fallback), which is a perfectly
     * good name — and for a slug like "shop_plus" that fallback is "Shop Plus",
     * so two themes can collide on a name nobody ever typed. Answering "unique"
     * for a blank name would let that through and hand the owner a duplicate they
     * did not make and cannot see.
     */
    public static function isNameUnique(string $name, ?string $excludeSlug = null): bool
    {
        return trim($name) !== '' && static::themeWithName($name, $excludeSlug) === null;
    }

    /**
     * The form a theme name is compared in: trimmed, case-folded, and with runs
     * of whitespace collapsed to single spaces.
     *
     * Case is folded because these names are rendered as plain text in the admin
     * picker and in the storefront footer, where "Shop" and "shop" are the same
     * word to whoever is reading them. Two themes differing only in case are not
     * a distinction anybody can act on — they are a duplicate with a typo in it,
     * and letting one past the check would then block the owner's later, correct
     * save of the other.
     *
     * The whitespace collapse is there for the same reason, and for the likelier
     * cause: a theme author hand-typing a manifest is exactly how a double space
     * gets in there.
     *
     * One definition, used by both callers above — the failure mode this guards
     * against is the two of them comparing names two slightly different ways,
     * which passes here and then contradicts the error message.
     */
    private static function nameKey(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));
    }

    /**
     * The serial number to hand the next theme: one past the highest one in use.
     *
     * Max-plus-one rather than a count of themes, so a deleted theme's number is
     * never handed out again. A serial that gets reused is a serial that refers
     * to two different themes across the life of a site, which defeats the point
     * of having one — and the gaps cost nothing, since nothing derives an
     * ordering or a row count from this number.
     *
     * A theme whose manifest carries no SN yet reads as 0 (see manifest()), so
     * the built-in themes are simply counted as the ones holding up the floor.
     */
    public static function nextSn(): int
    {
        $used = static::allSn();

        return $used === [] ? 1 : max($used) + 1;
    }

    /**
     * Whether a serial number is free. The check behind the SN field on the admin
     * manifest form, and the reason SNs are validated at all rather than only
     * assigned: an owner typing a number by hand is the one way two themes end
     * up sharing one.
     *
     * Zero is never free. It is the value a manifest with no SN reads back as
     * (see manifest()), so accepting it would be accepting the number that stands
     * for "this theme was never given one".
     */
    public static function isSnUnique(int $sn, ?string $excludeSlug = null): bool
    {
        if ($sn < 1) {
            return false;
        }

        return static::slugBySn($sn, $excludeSlug) === null;
    }

    /**
     * The theme holding a serial number, or null when it is free.
     *
     * $excludeSlug skips the theme being edited, so re-saving a theme's existing
     * SN is not read back as that SN being taken by itself.
     */
    public static function slugBySn(int $sn, ?string $excludeSlug = null): ?string
    {
        foreach (array_keys(static::all()) as $slug) {
            if ($slug === $excludeSlug) {
                continue;
            }

            if ((int) static::manifest($slug)['sn'] === $sn) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Every installed theme's serial number, as slug => SN.
     *
     * Read through manifest() rather than off the raw files, so the numbers here
     * are the same ones the admin form shows and the same ones the uniqueness
     * checks compare — a serial read any other way is a serial that can disagree
     * with the one it is supposed to be checking.
     *
     * @return array<string, int>
     */
    public static function allSn(): array
    {
        return collect(static::all())
            ->keys()
            ->mapWithKeys(fn (string $slug) => [$slug => (int) static::manifest($slug)['sn']])
            ->all();
    }

    /**
     * Drop the cached theme list so installs/uninstalls show up on the next
     * call to all() — called by the admin theme installer after it writes a
     * new folder, since all() otherwise caches the scan for a whole day.
     */
    public static function forget(): void
    {
        self::$all = [];
        Cache::forget('themes:all');

        static::forgetManifest();
    }

    /**
     * Drop one theme's memoed manifest, or every theme's when given no slug.
     *
     * manifest() normally notices a changed file by its mtime and size, which is
     * what catches a theme.json replaced from outside the app. It cannot catch a
     * theme.json this request has just written twice inside the same second,
     * though: a save that renames a theme and then reads its manifest back would
     * be handed the version from before the save. Anything writing a manifest and
     * reading it back in one request — the admin form, the installer — clears it
     * here rather than relying on the clock.
     *
     * Separate from forget() because the manifest memo is per-request while the
     * folder list behind all() is cached for a day: renaming a theme does not
     * change which folders exist, and throwing that cache away on every rename
     * would make a cheap action cost a rescan of the themes directory for the
     * next day of requests.
     */
    public static function forgetManifest(?string $slug = null): void
    {
        if ($slug === null) {
            self::$manifests = [];

            return;
        }

        foreach (array_keys(self::$manifests) as $memoKey) {
            unset(self::$manifests[$memoKey][$slug]);
        }
    }

    /**
     * A theme's own manifest — its name, description, version, author and tags —
     * read from the theme.json at the folder's root. A manifest is optional: when
     * a theme ships none, the slug-derived label, no author and version "1.0.0"
     * are returned instead, so every theme (installed or built-in) has a
     * well-formed manifest.
     *
     * That same file is where a theme keeps its settings (see
     * App\Support\ThemeSettings), so the fields picked out here are a handful of
     * keys out of a larger file and everything else in it is none of this
     * method's business.
     *
     * @return array{name: string, description: string, version: string, author: string, tags: array<int, string>, no_index: bool, sn: int}
     */
    public static function manifest(string $slug): array
    {
        $defaults = [
            'name' => ucwords(str_replace(['-', '_'], ' ', $slug)),
            'description' => '',
            'version' => '1.0.0',
            'author' => '',
            'tags' => [],
            'no_index' => false,
            'default' => false,
            'sn' => 0,
        ];

        $file = self::path().'/'.$slug.'/theme.json';
        $present = is_file($file);

        // Keyed on the file's mtime and size as well as the slug — same
        // stamp ThemeSettings::all() reads the same file under, so a file
        // this request itself just wrote is re-read rather than served from
        // a memo written before the write.
        $stamp = ($present ? 'file' : 'none')
            .':'.($present ? (int) filemtime($file) : 0)
            .':'.($present ? (int) filesize($file) : 0);

        $memoKey = spl_object_id(Cache::getFacadeRoot());

        if ((self::$manifests[$memoKey][$slug]['stamp'] ?? null) === $stamp) {
            return self::$manifests[$memoKey][$slug]['manifest'];
        }

        $manifest = $defaults;
        $data = $present ? json_decode((string) file_get_contents($file), true) : null;

        if (is_array($data)) {
            $manifest = [
                'name' => is_string($data['name'] ?? null) && $data['name'] !== '' ? $data['name'] : $defaults['name'],
                'description' => is_string($data['description'] ?? null) ? $data['description'] : '',
                'version' => is_string($data['version'] ?? null) && $data['version'] !== '' ? $data['version'] : $defaults['version'],
                'author' => is_string($data['author'] ?? null) ? $data['author'] : '',
                'tags' => collect($data['tags'] ?? [])->filter(fn ($tag) => is_string($tag) && $tag !== '')->values()->all(),
                // Only a literal true opts out. A theme that omits the key, or
                // spells it as a JSON string or the number 1, is saying nothing
                // and so is indexable — the reading that keeps a typo from
                // quietly unlisting a storefront.
                'no_index' => ($data['no_index'] ?? null) === true,
                // Same reading as no_index above, and for the same reason: the key
                // is what marks a theme as one of the ones that ships with the
                // system, so a typo has to fail towards "not default" rather than
                // leave a bundled theme quietly deletable.
                'default' => ($data['default'] ?? null) === true,
                'sn' => static::readSn($data['sn'] ?? null),
            ];
        }

        self::$manifests[$memoKey][$slug] = ['stamp' => $stamp, 'manifest' => $manifest];

        return $manifest;
    }

    /**
     * A manifest's raw "sn" value as the integer the rest of the app compares,
     * or 0 for a theme that has never been given one.
     *
     * theme.json is a file the owner is expected to open and edit, so this
     * arrives as whatever the JSON happened to hold: an integer if the app wrote
     * it, a numeric *string* if it was typed by hand into a quoted field, a
     * float if someone spelled it "3.0", and something that is not a number at
     * all if they typed a word. Only the integer is taken at face value; the rest
     * are coerced, and anything that cannot be is read as 0 rather than allowed
     * to become a string where every other SN in the app is an integer.
     *
     * 0 is the deliberate "no serial number yet" value rather than an error: it
     * is what a theme shipped without one looks like, and nextSn()/allSn() both
     * treat it as holding up the floor of the sequence instead of counting it.
     */
    private static function readSn(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^\s*-?\d+\s*$/', $value) === 1) {
            return (int) trim($value);
        }

        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        return 0;
    }

    /**
     * Whether the active theme wants search engines to keep what it renders.
     *
     * The flag lives in the theme's own theme.json next to its name and version,
     * because it is a property of the theme and not of a page: switching themes
     * has to be able to change the answer without anyone remembering to go and
     * edit a meta tag. The "default" theme is the case that matters — it is a
     * screen of login links, so it sends noindex,nofollow and is kept out of the
     * sitemap (see Seo\Sitemap), and it needs those two to be the same answer.
     */
    public static function isIndexable(?string $slug = null): bool
    {
        return ! self::manifest($slug ?? self::active())['no_index'];
    }

    /**
     * Whether a theme ships its own settings screen — i.e. a settings.blade.php
     * at the folder's root. The Theme Settings admin screen renders that view
     * inline whenever the theme is the one selected in the picker.
     */
    public static function hasSettings(string $slug): bool
    {
        return is_file(self::path().'/'.$slug.'/settings.blade.php');
    }

    /**
     * The theme to render at the public root URL — the admin-selected theme,
     * falling back to "default" (or the first available theme) if the selected
     * theme's folder no longer exists.
     */
    public static function active(): string
    {
        $available = self::all();
        $selected = Setting::get('site_theme', 'default');

        // all() is cached for a day, so a theme folder deleted since then is
        // still listed — confirm the folder really exists before trusting it.
        $exists = fn ($slug) => is_string($slug)
            && array_key_exists($slug, $available)
            && is_dir(self::path().'/'.$slug);

        if ($exists($selected)) {
            return $selected;
        }

        if ($exists('default')) {
            return 'default';
        }

        foreach (array_keys($available) as $slug) {
            if ($exists($slug)) {
                return $slug;
            }
        }

        return 'default';
    }

    /**
     * Whether the active theme ships its own copy of a storefront template.
     *
     * This is the single definition of "does this theme have this page?" —
     * nothing else is ever consulted, which is what makes a theme self-contained
     * (see view()). A dotted name is read as directories, so 'account.orders'
     * means account/orders.blade.php.
     */
    public static function has(string $name): bool
    {
        return static::hasTemplateFor(static::active(), $name);
    }

    /**
     * has() for a named theme rather than the active one — the same "does this
     * theme have this page?" question, asked about a theme other than the one
     * currently serving. The pairing is the point: a route a theme registers and
     * a template it doesn't ship would be a page that resolves and then 500s.
     */
    public static function hasTemplateFor(string $theme, string $name): bool
    {
        return is_file(static::templateFile($theme, $name));
    }

    /**
     * The theme template a route name renders through, or null when the route
     * isn't a themed page - an unnamed alias, a click tracker, a redirect.
     */
    public static function templateForRoute(string $name): ?string
    {
        return static::ROUTE_TEMPLATES[$name] ?? null;
    }

    /**
     * Whether the active theme can serve the page behind a route name.
     *
     * The routing-layer half of the rule Themes::view() applies when it looks for
     * a template: a page the active theme ships no template for is not part of
     * this site, whether that verdict is reached before the controller runs (see
     * App\Http\Middleware\EnsureActiveTheme) or after.
     *
     * A name ROUTE_TEMPLATES doesn't map is not a themed page at all, so there is
     * nothing here to answer and it counts as renderable.
     */
    public static function activeThemeCanRender(string $name): bool
    {
        $template = static::templateForRoute($name);

        return $template === null || static::hasTemplateFor(static::active(), $template);
    }

    /**
     * The dotted view path the active theme renders a storefront template from,
     * or null when the theme ships no such template.
     *
     * Themes are strictly self-contained: there is deliberately no fallback to
     * any other theme (not to "ecommerce", not to "default"). A page the active
     * theme has no template for does not exist on that site — it 404s — rather
     * than silently appearing in a design the admin never selected.
     */
    public static function view(string $name): ?string
    {
        if (! static::has($name)) {
            return null;
        }

        $theme = static::active();
        static::registerViews($theme);

        return static::viewNamespace($theme).'::'.$name;
    }

    /**
     * view() for the storefront routes: the active theme's template, or a 404
     * when the theme has none. Every public page goes through here rather than
     * reaching for Themes::active() and interpolating a view name itself, so a
     * theme missing a template degrades to "not found" instead of a 500 from an
     * unresolvable view.
     */
    public static function viewOrFail(string $name): string
    {
        return static::view($name) ?? abort(404, 'The "'.static::active()."\" theme has no \"{$name}\" template.");
    }

    /**
     * The error view for a status code in the active theme: its own
     * errors/{code}.blade.php when it ships one, otherwise Laravel's shared
     * resources/views/errors/{code}.blade.php.
     *
     * Every bundled theme ships its own 404, so a storefront error arrives in
     * the design the visitor was already browsing. The shared page is what a
     * theme with no errors/ folder gets — and what the admin panel, the portals
     * and the API get unconditionally (see bootstrap/app.php), which is why it
     * has to stay self-contained enough to render with no theme, no assets and
     * no queries.
     *
     * The shared page is named as a plain dotted path rather than the
     * "errors::404" namespace the framework's own error handler uses: that
     * namespace is never registered with the view finder here, so it only
     * resolves through the handler's private path search, not through view().
     */
    public static function errorView(int $code): string
    {
        $theme = static::active();

        if (! is_file(static::templateFile($theme, "errors/{$code}"))) {
            return "errors.{$code}";
        }

        static::registerViews($theme);

        return static::viewNamespace($theme)."::errors.{$code}";
    }

    /**
     * Whether a menu/nav link points at a page the active theme can actually
     * render — the "no dead links" half of theme scoping. With templates
     * theme-exclusive, a link to a page this theme has no template for would
     * 404 on click, so it is dropped from the nav instead of being advertised.
     *
     * Anything that isn't one of the themed storefront routes is always kept:
     * an external link, a "#section" same-page anchor, a hand-typed path the
     * router doesn't recognise, or a route with no template mapping (the
     * customer login, /admin's legacy bounce). Those are not this theme's
     * responsibility to judge.
     */
    public static function canRenderLink(?string $routeName, ?string $url): bool
    {
        $routeName ??= static::routeNameFor($url);

        if ($routeName === null || ! array_key_exists($routeName, self::ROUTE_TEMPLATES)) {
            return true;
        }

        return static::has(self::ROUTE_TEMPLATES[$routeName]);
    }

    /**
     * Which route a stored menu URL points at, or null when it isn't one of
     * ours. The menu admin form always saves a plain URL (see
     * Livewire\Admin\Menu\Index::save()), so this is how a menu item is matched
     * back to the route — and therefore the template — behind it.
     *
     * Every theme's routes are registered (see allRouteFiles()), so this matches
     * /shop on a portfolio site too even though that route will 404 there. That
     * is the point: a link to a page the active theme doesn't serve has to be
     * recognised as a themed storefront page in order to be dropped, and it
     * could not be if a non-active theme's routes were missing from the table.
     */
    private static function routeNameFor(?string $url): ?string
    {
        // A bare "#fragment" (see ThemesPortfolioDatabaseSeedersMenuSeeder), an absolute URL, or
        // anything that isn't a root-relative path isn't ours to resolve.
        if (! is_string($url) || ! str_starts_with($url, '/')) {
            return null;
        }

        try {
            return Route::getRoutes()->match(Request::create($url, 'GET'))->getName();
        } catch (NotFoundHttpException) {
            return null;
        }
    }

    /**
     * Whether a request belongs to the public storefront, as opposed to the
     * admin panel, the vendor/delivery portals or the API. Each of those has
     * its own host (see bootstrap/app.php) or its own JSON contract and must
     * keep the shared error pages rather than the active theme's storefront 404.
     */
    public static function isStorefrontRequest(Request $request): bool
    {
        if ($request->expectsJson() || $request->is('api', 'api/*', 'livewire', 'livewire/*')) {
            return false;
        }

        $host = $request->getHost();

        foreach (['admin_host', 'vendor_host', 'delivery_host'] as $key) {
            $panelHost = config('app.'.$key);

            if (is_string($panelHost) && $panelHost !== '' && $panelHost === $host) {
                return false;
            }
        }

        return true;
    }

    /**
     * The storefront pages every new theme is started with, as template name =>
     * the label its placeholder is titled with.
     *
     * One entry per route name in ROUTE_TEMPLATES, which is the whole list on
     * purpose. Themes are strictly self-contained (see view()): a page the active
     * theme ships no template for does not exist on that site, and canRenderLink()
     * drops it from the nav. Starting a theme off with a handful of templates
     * would therefore ship it as a site with holes in it, and the owner would
     * find them one dead nav link at a time.
     *
     * Every file is a working page that renders on its own — no controller
     * variables are read, because what any given controller passes is the one
     * thing a scaffold cannot know. Filling them in is the theme author's job;
     * having them all exist is not.
     *
     * @var array<string, string>
     */
    private const STARTER_TEMPLATES = [
        'home' => 'Home',
        'page' => 'Page',
        'shop' => 'Shop',
        'product' => 'Product',
        'category' => 'Category',
        'brand' => 'Brand',
        'tag' => 'Tag',
        'favorites' => 'Favorites',
        'blog' => 'Blog',
        'post' => 'Post',
        'cart' => 'Cart',
        'checkout' => 'Checkout',
        'order-confirmation' => 'Order confirmation',
        'account/dashboard' => 'Account dashboard',
        'account/orders' => 'Account orders',
        'account/order' => 'Account order',
        'account/profile' => 'Account profile',
    ];

    /**
     * Writes a brand-new theme folder from the basics typed on the Theme Settings
     * screen: every page template listed in STARTER_TEMPLATES, the header and
     * footer partials they share, the theme's own 404, a stylesheet, a settings
     * screen and a commented routes file — all inside the one folder, so the
     * theme can be zipped and handed to someone else whole.
     *
     * The theme is not activated. Creating it and choosing it are two decisions,
     * and picking a design is the owner's to make on the picker above: a
     * half-built theme that went live the moment it was created would replace a
     * working site with seventeen placeholders.
     *
     * @param  array{name: string, slug: string, version: string, description: string, author: string}  $data
     *
     * @throws \RuntimeException with a message safe to show the admin
     */
    public static function create(array $data): string
    {
        $slug = $data['slug'];

        if (! ThemeSettings::isValidSlug($slug)) {
            throw new \RuntimeException('The theme slug must use letters, numbers, dashes and underscores only.');
        }

        if (file_exists(static::path().'/'.$slug)) {
            throw new \RuntimeException("A theme named \"{$slug}\" already exists.");
        }

        if (! static::isNameUnique($data['name'])) {
            throw new \RuntimeException("Another installed theme is already called \"{$data['name']}\". Theme names have to be unique.");
        }

        foreach (static::STARTER_TEMPLATES as $template => $label) {
            static::writeThemeFile($slug, $template.'.blade.php', static::starterTemplate($slug, $template, $label));
        }

        static::writeThemeFile($slug, 'errors/404.blade.php', static::starterError($slug));
        static::writeThemeFile($slug, 'partials/header.blade.php', static::starterHeader($slug));
        static::writeThemeFile($slug, 'partials/footer.blade.php', static::starterFooter());
        static::writeThemeFile($slug, 'public/css/theme.css', static::starterStylesheet($slug));
        static::writeThemeFile($slug, 'settings.blade.php', static::starterSettings());
        static::writeThemeFile($slug, 'routes/web.php', static::starterRoutes($slug));

        // Last, because it is the one file the folder is not finished without:
        // ThemeSettings::create() seeds it from Themes::manifest() and gives it a
        // serial number, and it refuses to write one into a folder that does not
        // exist yet — which is the point, the folder above is what makes this
        // theme rather than a file somebody dropped in themes/.
        if (! ThemeSettings::create($slug, array_filter([
            'name' => $data['name'],
            'description' => $data['description'],
            'version' => $data['version'],
            'author' => $data['author'],
        ], fn ($value) => trim((string) $value) !== ''))) {
            static::forget();

            throw new \RuntimeException("The \"{$slug}\" theme folder was written, but its ".ThemeSettings::FILE.' could not be created (is the themes directory writable?).');
        }

        static::forget();
        static::registerViews($slug);

        return $slug;
    }

    /**
     * Whether a theme carries `"default": true` in its theme.json — the flag that
     * marks it as one of the themes the system ships with, the same thing
     * `"default": true` means in a plugin.json.
     *
     * A theme created here or installed from a zip never sets it, so nothing the
     * owner adds can lock itself out of deletion. The bundled themes set it by
     * hand in their own theme.json, which is also why a theme handed to someone
     * else carries its own protection with it.
     */
    public static function isDefault(string $slug): bool
    {
        return static::manifest($slug)['default'] === true;
    }

    /**
     * Why a theme cannot be deleted, or null when it can.
     *
     * Three separate reasons, checked in the order the owner would want them
     * explained, because each one is a different mistake to undo:
     *
     *  - it says `"default": true`, so it is one of the bundled themes;
     *  - it is the live theme (the site_theme setting), so deleting it would take
     *    the storefront down until another theme was picked and saved;
     *  - it is the only theme installed, so there would be nothing left for the
     *    storefront to render in at all.
     *
     * Returned as a message rather than a bool because the admin screen puts the
     * reason on the disabled Delete button, and a greyed-out control that will not
     * say why is the thing that sends people to the console.
     */
    public static function undeletableBecause(string $slug): ?string
    {
        if (static::isDefault($slug)) {
            return 'This theme ships with Codeware, so it cannot be deleted.';
        }

        if ($slug === static::active()) {
            return 'This theme is live. Pick another theme and save first.';
        }

        if (count(static::all()) < 2) {
            return 'This is the only installed theme. Install or create another one first.';
        }

        return null;
    }

    /**
     * Removes a theme folder and everything in it — templates, controllers,
     * migrations, seeders, its public/ files and its theme.json, which is where
     * its settings live, so those go with it.
     *
     * Refuses everything undeletableBecause() refuses, so the live theme and the
     * last one standing cannot be removed out from under the storefront even if
     * the screen is bypassed and the method called directly.
     *
     * What it cannot undo is the theme's database migrations, which ran when the
     * theme was activated or installed and have no down path in a theme folder.
     * That is what the confirmation modal is for.
     */
    public static function delete(string $slug): bool
    {
        if (! static::isInstalled($slug) || static::undeletableBecause($slug) !== null) {
            return false;
        }

        File::deleteDirectory(static::path().'/'.$slug);
        static::forget();

        return true;
    }

    /**
     * Whether $slug is the name of a theme that is actually installed, as opposed
     * to just a well-formed path fragment.
     *
     * The two are not the same thing and the difference is the whole point of
     * this method: `themes/../storage` is a real directory, so a caller that only
     * checked the slug's shape would let "../storage" through, and
     * File::deleteDirectory() or a zip walk does not care that it was told to.
     * Both delete() and toZip() touch the filesystem on a name that arrived from
     * the admin form, so both go through here first.
     */
    public static function isInstalled(string $slug): bool
    {
        return ThemeSettings::isValidSlug($slug)
            && array_key_exists($slug, static::all())
            && is_dir(static::path().'/'.$slug);
    }

    /**
     * Packs an installed theme back into a zip holding that one folder — the same
     * shape Theme Settings' install accepts, so a theme can be backed up before it
     * is hand-edited, or handed to someone else as a shareable file.
     *
     * Returns the temp path of the archive; the caller sends it and cleans it up.
     *
     * @throws \RuntimeException with a message safe to show the admin
     */
    public static function toZip(string $slug): string
    {
        if (! static::isInstalled($slug)) {
            throw new \RuntimeException('That theme is not installed.');
        }

        $path = tempnam(sys_get_temp_dir(), 'theme-export-');
        @unlink($path);

        $zip = new \ZipArchive;

        if ($zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create the zip file.');
        }

        static::addToZip($zip, static::path().'/'.$slug, $slug);
        $zip->close();

        return $path;
    }

    /**
     * Adds a theme folder to an archive under one top-level {slug}/ folder, which
     * is the shape the installer looks for.
     *
     * The folder is copied whole, with no exclusions, because the folder *is* the
     * unit that gets handed over: dropping its database/ or public/ on the way out
     * would make the archive install into something that is missing half of
     * itself. Editor and OS droppings are the one exception — they are not part of
     * the theme, and a .DS_Store from a Mac is how an archive picks up a slug that
     * the receiving side cannot use.
     */
    private static function addToZip(\ZipArchive $zip, string $absoluteDir, string $localPrefix): void
    {
        $zip->addEmptyDir($localPrefix);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absoluteDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            $localPath = $localPrefix.'/'.ltrim(
                str_replace('\\', '/', substr($item->getPathname(), strlen($absoluteDir))),
                '/',
            );

            if ($item->isDir()) {
                $zip->addEmptyDir($localPath);
            } elseif (! static::isJunk($item->getFilename())) {
                $zip->addFile($item->getPathname(), $localPath);
            }
        }
    }

    /**
     * Whether a filename is an editor or OS artefact rather than part of the theme.
     */
    private static function isJunk(string $name): bool
    {
        return $name === '.DS_Store'
            || $name === 'Thumbs.db'
            || str_starts_with($name, '._')
            || str_ends_with($name, '~')
            || in_array(strtolower($name), ['__macosx', '.git', 'node_modules'], true);
    }

    /**
     * Write one file inside a theme folder, making the directories it needs on the
     * way. File::put() does not create a parent directory, and most of what a
     * theme is made of lives in one (errors/, partials/, account/).
     */
    private static function writeThemeFile(string $slug, string $relative, string $contents): void
    {
        $file = static::path().'/'.$slug.'/'.$relative;

        File::ensureDirectoryExists(dirname($file));
        File::put($file, $contents);
    }

    private static function starterTemplate(string $slug, string $template, string $label): string
    {
        return str_replace(
            ['{slug}', '{label}', '{page}'],
            [$slug, $label, $template],
            <<<'BLADE'
            {{-- The {label} page of the "{slug}" theme.

                 One file per page, all of them in this folder — see
                 App\Support\Themes::STARTER_TEMPLATES for the list and
                 ROUTE_TEMPLATES for the route name each one answers. This is a
                 working placeholder: it renders as it stands, reads no variables
                 and links nowhere but home. Replace the <main> with your own
                 markup; the head, the header and the footer around it are the
                 theme's own and can stay. --}}
            <!DOCTYPE html>
            <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
            <head>
                @include('partials.head')
                @include('partials.seo-meta')
                @include('partials.custom-code-head')
            </head>
            <body class="bg-white text-zinc-800 antialiased">

            @include('theme-{slug}::partials.header')

            <main>
                <section class="mx-auto max-w-2xl px-6 py-24 text-center">
                    <h1 class="text-3xl font-bold text-zinc-900 sm:text-4xl">{label}</h1>
                    <p class="mt-4 text-sm text-zinc-500">
                        This is the "{slug}" theme's {page} page. Replace this section with your own markup.
                    </p>
                </section>
            </main>

            @include('theme-{slug}::partials.footer')

            @include('frontend.partials.chat-widget')
            @include('partials.custom-code-body')
            </body>
            </html>
            BLADE
        );
    }

    private static function starterError(string $slug): string
    {
        return str_replace(
            ['{slug}'],
            [$slug],
            <<<'BLADE'
            {{-- The "{slug}" theme's 404.

                 Themes::errorView() prefers a theme's own errors/{code}.blade.php
                 over the shared resources/views/errors/{code}.blade.php, so this
                 is what a visitor who hits a dead URL on this theme actually gets
                 — in the theme's own header, footer and stylesheet rather than the
                 shared shell. Delete it and the site falls back to that shared
                 page instead. --}}
            <!DOCTYPE html>
            <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
            <head>
                @include('partials.head')
                {{-- A 404 must never be indexed, and must never claim a canonical
                     URL for a page that does not exist — so partials.seo-meta is
                     deliberately absent here (it derives both from $page). --}}
                <meta name="robots" content="noindex, nofollow">
                @include('partials.custom-code-head')
            </head>
            <body class="bg-white text-zinc-800 antialiased">

            @include('theme-{slug}::partials.header')

            <main>
                <section class="mx-auto flex max-w-4xl flex-col items-center gap-8 px-6 py-24 text-center">
                    <h1 class="text-7xl font-extrabold leading-none tracking-tight text-zinc-900">404</h1>

                    <div>
                        <h2 class="text-2xl font-bold text-zinc-900">{{ __('Sorry, page not found') }}</h2>
                        <p class="mt-3 text-zinc-600">{{ __('The page you requested could not be found. It may have been moved, renamed, or removed.') }}</p>

                        <a href="{{ url('/') }}" class="mt-8 inline-block rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                            {{ __('Back to homepage') }}
                        </a>
                    </div>
                </section>
            </main>

            @include('theme-{slug}::partials.footer')

            @include('frontend.partials.chat-widget')
            @include('partials.custom-code-body')
            </body>
            </html>
            BLADE
        );
    }

    private static function starterHeader(string $slug): string
    {
        return str_replace(
            ['{slug}'],
            [$slug],
            <<<'BLADE'
            {{-- The "{slug}" theme's header, shared by every page of this theme
                 (its templates and its 404), which is why it is a partial rather
                 than markup repeated in seventeen files.

                 Reads the CMS pages itself rather than expecting the including
                 view to hand them over, so a new page needs nothing but its own
                 @include to be navigable. --}}
            @php
                $siteName = \App\Models\Setting::get('site_name', config('app.name'));
                $siteIcon = \App\Models\Setting::get('site_icon');
                $navPages = \App\Support\Frontend::navPages();
            @endphp

            <header class="sticky top-0 z-20 border-b border-zinc-100 bg-white/90 backdrop-blur">
                <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                    <a href="{{ url('/') }}" class="flex items-center gap-2">
                        @if ($siteIcon)
                            <img src="{{ $siteIcon }}" alt="{{ $siteName }}" class="h-8 w-auto">
                        @endif
                        <span class="text-lg font-bold text-zinc-900">{{ $siteName }}</span>
                    </a>

                    <nav class="hidden items-center gap-6 md:flex">
                        @foreach ($navPages as $navPage)
                            <a href="{{ $navPage->slug === 'home' ? url('/') : url('/'.$navPage->slug) }}"
                                class="text-sm font-medium text-zinc-600 hover:text-zinc-900">
                                {{ $navPage->getTranslation('title', 'en', false) }}
                            </a>
                        @endforeach
                    </nav>
                </div>
            </header>
            BLADE
        );
    }

    private static function starterFooter(): string
    {
        return <<<'BLADE'
        {{-- The theme's footer, shared by every page for the same reason its
             header is. Reads its own settings. --}}
        @php
            $siteName = \App\Models\Setting::get('site_name', config('app.name'));
        @endphp

        <footer class="border-t border-zinc-100 bg-zinc-50 px-6 py-10">
            <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
                <p class="text-sm text-zinc-500">&copy; {{ now()->setTimezone(display_timezone())->year }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
            </div>
        </footer>
        BLADE;
    }

    /**
     * The theme's own stylesheet, in its own folder — Themes::storefrontEntry()
     * serves it, and vite.config.js scans every themes/{slug}/public/css/theme.css
     * for the same reason.
     */
    private static function starterStylesheet(string $slug): string
    {
        return str_replace(
            ['{slug}'],
            [$slug],
            <<<'CSS'
            /* The "{slug}" theme stylesheet — its own Vite entry, so a page of this
               theme downloads the utility classes its own templates use and not
               every other theme's. See Themes::storefrontEntry().

               The shared half (base.css and storefront-shared.css) is imported
               rather than copied, exactly as the bundled themes do it. The
               @source lines collect the utility classes from this theme's own
               templates; delete them and none of the classes below are generated.

               Paths are relative to this file, themes/{slug}/public/css/theme.css:
               two levels up is the theme folder and four are the project root, so
               the @source below collects the theme's whole folder from inside
               public/ rather than only public/ itself. */
            @import '../../../../resources/css/base.css';
            @import '../../../../resources/css/storefront-shared.css';

            @source '../..';

            /* The admin Theme Settings screen renders settings.blade.php inline,
               and the picker markup in it is on no storefront page. */
            @source not '../../settings.blade.php';

            /* Your own rules go below this line. */
            CSS
        );
    }

    private static function starterSettings(): string
    {
        return <<<'BLADE'
        {{-- This theme's settings screen (Admin → Theme Settings). Rendered inline
             into the panel, one root element, and everything on it is saved into
             this theme's own theme.json inside this folder rather than the
             database — so the folder can be zipped and handed to someone else with
             its content still in it.

             A field is declared by its binding: any settings.theme_<slug>_…
             key below is discovered by parsing this file, so a new field appears
             here on the next load and saves like any other. It must start with
             "theme_" followed by this theme's own slug, so two themes cannot end
             up writing over each other's values.

             Scalar fields bind to wire:model="settings.…"; a list of rows
             (repeater, gallery, anything with add/remove) is declared with
             <x-admin-repeatable-fields setting-key="theme_<slug>_…"> instead, which
             is a separate bag precisely so a list is never written back as a
             string. Repeaters may add :max="…" for a row cap.

             No fields yet — the panel below is the whole thing this theme has to
             say for itself until it declares some. }}
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50/60 p-5 dark:border-zinc-600 dark:bg-zinc-800/30">
            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ ucwords(str_replace(['-', '_'], ' ', $themeSlug)) }} settings
            </p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                This theme does not define any settings yet. Add a form field below, keyed
                <code class="font-mono">settings.theme_&lt;slug&gt;_…</code>, and it will
                show up here and be saved into this theme's own
                <code class="font-mono">{{ \App\Support\ThemeSettings::FILE }}</code>.
            </p>
        </div>

        @fluxScripts
        BLADE;
    }

    private static function starterRoutes(string $slug): string
    {
        return str_replace(
            ['{slug}'],
            [$slug],
            <<<'PHP'
            <?php

            /*
             * The storefront routes of the "{slug}" theme — in this folder, beside
             * the templates they render.
             *
             * routes/web.php registers this file behind the 'theme' guard, which
             * 404s any request the active theme has no template for, so everything
             * here answers only while this theme is the active one. A theme with no
             * route file at all (this one, as shipped) registers no storefront URLs
             * and is flagged as such on the theme picker.
             *
             * A route name means the same page in every theme's file, and the 'theme'
             * guard is keyed on that name — so a route and the template of the same
             * name have to be added together. The full list of names is
             * Themes::ROUTE_TEMPLATES, and this theme ships one template for each
             * of them (see Themes::STARTER_TEMPLATES).
             *
             * Controllers live in this folder's Controllers/ directory (namespace
             * Themes\{Slug}\Controllers, autoloaded — no composer dump needed), one
             * class per page, and render through Themes::view() so the page comes out of this
             * theme rather than whichever one happens to be active:
             *
             *     public function home()
             *     {
             *         return $this->view('home');
             *     }
             */

            use Illuminate\Support\Facades\Route;

            // Left commented rather than shipped pointing at a class that does not
            // exist: routes/web.php registers this file on every request, so a live
            // route to a missing controller would take the whole site down until the
            // file was edited. Uncomment it once the controller is written.
            //
            // Route::get('/', [\Themes\YourTheme\Controllers\HomeController::class, 'home'])->name('home');
            PHP
        );
    }

    /**
     * A theme template's path on disk. A dotted name is read as directories, so
     * 'account.orders' means account/orders.blade.php — a plain concatenation
     * would look for a file literally named "account.orders.blade.php", which no
     * theme can ship, and quietly send every nested template to the fallback.
     */
    private static function templateFile(string $theme, string $name): string
    {
        return static::path().'/'.$theme.'/'.str_replace('.', '/', $name).'.blade.php';
    }
}
