# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Codeware is a two-part project:
- **Laravel backend** (this directory): Admin panel (Livewire/Flux) + REST API
- **Next.js frontend** (`codeware-frontend/`): Public-facing site (Next.js 16, React 19 — early scaffold, not yet built)

## Commands

### PHP / Laravel (run from `d:\herd\codeware`)

PHP binary: `C:\Users\User\.config\herd\bin\php85\php.exe`

```bash
composer dev          # Start server + queue + Vite concurrently
composer test         # config:clear → lint:check → pest (full CI suite)
composer lint         # pint --parallel (auto-fix)
composer lint:check   # pint --parallel --test (lint without fixing)

php artisan test                          # Run all tests
php artisan test --filter "test name"     # Run a single test
./vendor/bin/pest --filter "test name"    # Alternative single test
php artisan migrate                       # Run migrations
php artisan tinker                        # REPL
```


### Next.js (run from `codeware-frontend/`)

```bash
npm run dev     # Development server (localhost:3000)
npm run build   # Production build
npm run lint    # ESLint
```

> **Warning**: Next.js 16.x has breaking API/convention changes from prior versions. Read `node_modules/next/dist/docs/` before writing frontend code.

## Architecture

### Authentication & Authorization

- Auth is handled by **Laravel Fortify** (login/register/2FA)
- Admin access is gated by Spatie roles (`admin`, `staff`) on the `User` model — there is no separate super-admin flag
- Gate `access-admin` is defined in `AppServiceProvider` — used by both `AdminMiddleware` (web) and `can:access-admin` (API)
- After login, admins redirect to `admin.dashboard`; regular users go to `dashboard`
- API admin endpoints require `auth:sanctum` + `can:access-admin`

### Admin Panel (Livewire)

Routes are in `routes/admin.php` under the `admin.*` named route group. Every route maps directly to a Livewire component in `app/Livewire/Admin/`. Components render using the `layouts.admin` blade layout with Flux UI (`flux:sidebar`, `flux:icon.*`, etc.).

### REST API (`/api/v1/`)

Two tiers in `routes/api.php`:

| Tier | Prefix | Auth |
|------|--------|------|
| Public (read-only) | `/api/v1/` | None |
| Admin (CRUD) | `/api/v1/admin/` | `auth:sanctum` + `can:access-admin` |

Controllers live in `app/Http/Controllers/Api/V1/` (public) and `app/Http/Controllers/Api/V1/Admin/` (admin).

Public API supports `?locale=en|bn` query parameter for translated fields, and `?per_page=N` pagination with a `{data, meta}` response envelope.

### Models & Domain

| Domain | Models | Notes |
|--------|--------|-------|
| CMS | `Post`, `Page`, `Category`, `Tag`, `PageRevision` | Page auto-creates revisions on content update |
| Products | `Product`, `ProductCategory` | Products have `puck_data` (JSON) for visual editor |
| Media | `MediaLibrary` | Custom (not Spatie); exposes `url` via Storage accessor |
| Settings | `Setting` | Key-value store, cached with `Cache::rememberForever` |

**Soft deletes**: `Post`, `Page`, `Product`, `ProductCategory` all use `SoftDeletes`.

**Translatable fields** (via `spatie/laravel-translatable`, locales `en`/`bn`): title/name, content/description, excerpt, SEO fields. Stored as JSON. When reading from a translatable field that may be an array, always check `is_array()` — see existing models for the pattern.

**Slug auto-generation**: All slug-able models generate slugs from the `en` value in their `booted()` `saving` hook. Slugs are not regenerated if already set.

### Plugins

Modular plugins live in `plugins/{slug}/` (registry: `App\Support\Plugins`, wiring: `PluginServiceProvider`). Each needs `plugin.json` (name, version, description, author, icon, `default`) and `index.blade.php` (its management screen, rendered at `admin.plugins.show` inside the admin layout with `$plugin` in scope). Optional: `routes.php` (mounted at `/plugins/{slug}/…`, names `admin.plugins.{slug}.*`), `migrations/`, extra views (`plugin-{slug}::name`). Install/activate/remove at Plugins → Plugin Settings (`admin.plugin-settings`); active slugs are stored in the `plugins_active` setting. `"default": true` plugins are always on and undeletable. The sidebar "Plugins" dropdown is built at runtime by `Plugins::extendMenu()` (not stored in `menu_items`).

### Themes

Themes are modules too, in `themes/{slug}/` (registry: `App\Support\Themes`, settings in `App\Support\ThemeSettings`, wiring: `ThemeServiceProvider`). Everything a theme is made of lives in its folder, so the folder is the unit that gets zipped and handed over. Don't put theme files back in `resources/`, `routes/` or `public/`.

```
themes/{slug}/
    theme.json                manifest + settings values in one file
    settings.blade.php        its admin settings screen
    routes/web.php            its storefront routes (optional)
    *.blade.php               templates, view namespace theme-{slug}:: (partials/, errors/, account/ ...)
    Controllers/              namespace Themes\{Slug}\Controllers — autoloaded, no composer dump
    database/migrations/      auto-loaded via loadMigrationsFrom()
    database/seeders/         namespace Themes\{Slug}\Database\Seeders — NOT auto-discovered
    public/                   everything the web serves, at /themes/{slug}/...
        css/theme.css         its own Vite entry (optional)
        css/, js/, img/       hand-written static files, served as-is
```

The folder name **is** the slug (`^[A-Za-z0-9_-]+$`), and `Themes::all()` is a `scandir` of `themes/` cached for a day under `themes:all` — after adding a folder by hand, `php artisan cache:clear`.

**How a request flows:** the active theme is the `site_theme` setting (Admin → Theme Settings). Every installed theme's `routes/web.php` is registered, each wrapped in `['theme', 'referral']` (`routes/web.php:141-143`). The `theme` guard is `EnsureActiveTheme`, which looks up the matched route's *name* in `Themes::ROUTE_TEMPLATES` and 404s if the active theme ships no template for it. It is prepended ahead of `auth` in `bootstrap/app.php` on purpose, so a page the active theme doesn't have answers 404 rather than redirecting to login.

**Hard rules (each one is a test in `tests/Feature/Frontend/ThemeScopedRoutesTest.php`):**

- **No template fallback between themes.** `Themes::view()` never falls back — a page the active theme has no template for does not exist on that site. `MenuItem::isRenderableByCurrentTheme()` applies the same rule, so nav links to such pages are dropped. Always render through `Themes::viewOrFail()` / `ThemeController::view()`; never hand-build a view name.
- **`Themes::ROUTE_TEMPLATES` is a hand-maintained mirror** of the route names across the theme route files: 17 entries (`home`, `page`, `shop`, `products.show`, `shop.category`, `shop.brand`, `shop.tag`, `favorites`, `blog`, `blog.post`, `cart`, `checkout`, `checkout.confirmation`, `account.*`). A new route name absent from it is invisible to the guard and always kept in the nav. Every theme must register `home`.
- **All themes' route files are registered on purpose** — a theme's file is the whole description of that theme's site, but switching theme must not require rebuilding the route table, and `route('shop')` in shared code has to resolve. Don't "tidy" that loop into an active-theme-only load.
- **Route names shared between themes must mean the same method + URI everywhere.**
- **Storefront 404s render the active theme's own `errors/404.blade.php`** via `Themes::errorView(404)`; only storefront requests qualify (`Themes::isStorefrontRequest()` excludes admin/vendor/delivery hosts and `api/*`).

**Shared base classes stay in `app/Http/Controllers/Themes/`** — `ThemeController` (plus the `Renders*` concerns: `RendersHomePage`, `RendersStandalonePage`, `RendersCatalog`, `RendersProduct`, `RendersCart`, `RendersAccount`, `RendersBlog`). A theme's controllers extend `ThemeController` and `use` the concern they need. Not theme-prefixed — the traits belong to no one theme.

**CSS:** `vite.config.js` scans `themes/*/public/css/theme.css` and registers each as a Vite input named `theme-{slug}` (the slug is in the name because every file is called `theme.css` and same-basename inputs collapse into one entry). `Themes::storefrontEntry()` returns the active theme's own entry when it has one, else the catch-all `storefront.css`. `resources/css/base.css` imports Tailwind with `source(none)`, so **every `theme.css` must declare its own `@source`** — otherwise its templates render unstyled. The entry lives inside `public/`, so its `@source` is relative to `themes/{slug}/public/css/`: `'../..'` is the theme folder and `'../../../../resources/css/…'` the project root. Getting that one level wrong silently scans *every* theme, and Vite then collapses the three identical bundles into one shared chunk. Nothing needs a `npm run build` config edit when a theme is added — but a theme moved or added does need a rebuild, or `@vite` 404s on the stale manifest key.

**Assets:** `ThemeAssetController` serves `themes/{slug}/public/{path}` at `/themes/{slug}/{path}` — unknown theme → 404, `realpath()` must still sit under that theme's `public/` (traversal blocked), and the MIME type comes from a fixed allow-list. Don't symlink a theme's `public/` into the app's; the route is the only supported path. `css/theme.css` is the one file it refuses: it's a Tailwind *source* full of `@import`/`@source`, so served raw it does nothing — the compiled sheet is what reaches the page.

**Settings:** theme settings live in the theme's own `theme.json`, never the `settings` table — `MANIFEST_KEYS` (`name`, `description`, `version`, `author`, `tags`, `no_index`, `sn`, `default`) are manifest, *everything else is a setting*, keys keep their `theme_{slug}_*` names. Read with the Blade helpers (`theme_setting()`, `theme_rows()`, `theme_color()`, `theme_manifest()`, `theme_json()`), each taking an optional trailing `$slug` so a non-active theme can read its own settings. `ThemeSettings::file()` rejects any slug outside `^[A-Za-z0-9_-]+$`, and an absent/empty/unparseable file reads as *no settings* rather than an error. Admin → Theme Settings renders the selected theme's `settings.blade.php` inline; its root element needs a `wire:key` or Alpine keeps whichever `x-data` was morphed in first.

**Download & delete**: both live in the three-dot menu on each Site Design card, not in a section of their own — one card is one theme — and every click there stops propagation, because the cards are `<label>` elements wrapping the radio and a button inside one without that toggles the theme as a side effect.

`Themes::toZip()` packs a folder into the one-top-level-folder shape the installer accepts, so a downloaded theme re-installs whole — the folder *is* the unit, so `database/` and `public/` are included, and only editor/OS droppings (`.DS_Store`, `Thumbs.db`, `._*`, `__MACOSX`, `.git`, `node_modules`) are skipped. Download is offered on *every* theme, bundled ones included; back one up before hand-editing it.

`Themes::delete()` is the reverse and refuses via `undeletableBecause()`, which returns the reason rather than a bool so the menu can print it in place of the action instead of greying out a button. Three things refuse it: the live theme, the last theme installed, and `themes/default` — the last only because its manifest sets `"default": true`, which no other bundled theme does. **Being bundled is not protection**: `ecommerce` and `portfolio` ship with the release and are deletable like anything else, because falling back to a specific folder is not the same as being able to lose it.

**A delete cannot undo a theme's migrations** — they ran on activation and have no down path in the folder, which is what the modal warns about. Both actions take a slug off the wire, and `themeToDelete` is a public property settable without the modal, so both go through `Themes::isInstalled()` first: `themes/../storage` is a real directory, and a well-formed-slug check alone would let it through to `File::deleteDirectory()` or a zip walk.

**Gotchas:** theme seeders are not auto-discovered — add them to `DatabaseSeeder`. `ThemeServiceProvider::boot()` runs before migrations during `migrate:fresh`, which is why it try/catches around `Themes::all()`. Creating a theme (Admin → Theme Settings → New Theme, `Themes::create()`) does **not** activate it; activation is a separate save.

### Settings Cache

`Setting::get($key)` is cached forever. Always use `Setting::set($key, $value)` (not direct `update()`) to write, as it busts the cache. If you update settings directly in migrations or seeders, manually call `Cache::forget("setting:{$key}")`.

### Migrations

**One migration file per table.** Each table's full final schema lives in a single `create_*_table` migration — never create separate `add_*`/`change_*`/`rename_*` migration files. To add/change a column, edit the table's existing create migration directly (then run `php artisan migrate:fresh` locally to replay).

Notes:
- All table migrations run in filename (timestamp) order, so FK-referenced tables must be created before their dependents (e.g. `pages` references `products`, so `pages` is timestamped after `products`).
- Blog categories use the `blog_categories` table (via `BlogCategory` model), not `categories`.
- If a schema change also needs a data backfill, put the `DB::table(...)` logic in the create migration too, or run a separate seeder.

### Frontend Stack (`codeware-frontend/`)

| Package | Purpose |
|---------|---------|
| Next.js 16 / React 19 | Framework |
| Tailwind CSS v4 | Styling |
| TanStack Query | Server state / data fetching |
| Zustand | Client state |
| Zod + React Hook Form | Form validation |
| @measured/puck | Visual page editor (tied to `puck_data` on Product) |
| Framer Motion | Animations |

## Testing Conventions

- Tests use **Pest v4** with `pestphp/pest-plugin-laravel`
- Organized by domain under `tests/Feature/`: `Auth/`, `Cms/`, `Products/`
- Factories support `->published()` and `->draft()` states for `Post`/`Product`
- Translatable factory fields pass both locales: `['title' => ['en' => 'English', 'bn' => '']]`
- API tests assert the `{data, meta}` envelope shape; single-resource tests assert `{data: {...}}`
