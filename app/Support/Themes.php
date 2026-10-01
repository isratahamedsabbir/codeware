<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
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

    public static function path(): string
    {
        return resource_path('views/frontend/themes');
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
     * Where the per-theme route files live — routes/web/{slug}.php, one per theme
     * folder, mirroring this class's one-template-per-page convention on the
     * routing side.
     */
    public static function routesPath(): string
    {
        return base_path('routes/web');
    }

    /**
     * Where the per-theme stylesheets live — resources/css/themes/{slug}/theme.css,
     * one per theme folder, the same one-file-per-theme rule as the route files
     * above and for the same reason: a theme is a folder you can zip up and hand
     * to someone else, and a file that only half a theme lives in is a file the
     * next person cannot find.
     */
    public static function stylesheetsPath(): string
    {
        return resource_path('css/themes');
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
        return is_file(static::stylesheetsPath().'/'.$theme.'/theme.css');
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
            ? 'resources/css/themes/'.$theme.'/theme.css'
            : 'resources/css/storefront.css';
    }

    /**
     * Whether a given theme ships a route file at all. A theme without one
     * simply contributes no routes — the same rule as a template it doesn't
     * ship — so its public surface is whatever routes/web.php gives every theme.
     */
    public static function routeFileExists(string $theme): bool
    {
        return is_file(static::routesPath().'/'.$theme.'.php');
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
            ->mapWithKeys(fn (string $slug) => [$slug => static::routesPath().'/'.$slug.'.php'])
            ->all();
    }

    /**
     * Every theme folder under resources/views/frontend/themes, as slug => label.
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
        return static::has($name) ? 'frontend.themes.'.static::active().'.'.$name : null;
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

        return is_file(static::templateFile($theme, "errors/{$code}"))
            ? "frontend.themes.{$theme}.errors.{$code}"
            : "errors.{$code}";
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
        // A bare "#fragment" (see PortfolioMenuSeeder), an absolute URL, or
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
