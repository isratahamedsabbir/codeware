# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AgroSAL is a two-part project:
- **Laravel backend** (this directory): Admin panel (Livewire/Flux) + REST API
- **Next.js frontend** (`agrosal-frontend/`): Public-facing site (Next.js 16, React 19 — early scaffold, not yet built)

## Commands

### PHP / Laravel (run from `d:\herd\agrosal`)

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


### Next.js (run from `agrosal-frontend/`)

```bash
npm run dev     # Development server (localhost:3000)
npm run build   # Production build
npm run lint    # ESLint
```

> **Warning**: Next.js 16.x has breaking API/convention changes from prior versions. Read `node_modules/next/dist/docs/` before writing frontend code.

## Architecture

### Authentication & Authorization

- Auth is handled by **Laravel Fortify** (login/register/2FA)
- Admin access is gated by `is_admin` (boolean) on the `User` model
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
| Commerce | `Dealer` | Has lat/lng, district/upazila; linked to `ProductCategory` via pivot |
| Career | `Department`, `Job`, `JobApplication` | `Job` uses table `career_jobs` |
| Media | `MediaLibrary` | Custom (not Spatie); exposes `url` via Storage accessor |
| Settings | `Setting` | Key-value store, cached with `Cache::rememberForever` |

**Soft deletes**: `Post`, `Page`, `Product`, `ProductCategory`, `Dealer`, `Job` all use `SoftDeletes`.

**Translatable fields** (via `spatie/laravel-translatable`, locales `en`/`bn`): title/name, content/description, excerpt, SEO fields. Stored as JSON. When reading from a translatable field that may be an array, always check `is_array()` — see existing models for the pattern.

**Slug auto-generation**: All slug-able models generate slugs from the `en` value in their `booted()` `saving` hook. Slugs are not regenerated if already set.

### Settings Cache

`Setting::get($key)` is cached forever. Always use `Setting::set($key, $value)` (not direct `update()`) to write, as it busts the cache. If you update settings directly in migrations or seeders, manually call `Cache::forget("setting:{$key}")`.

### Frontend Stack (`agrosal-frontend/`)

| Package | Purpose |
|---------|---------|
| Next.js 16 / React 19 | Framework |
| Tailwind CSS v4 | Styling |
| TanStack Query | Server state / data fetching |
| Zustand | Client state |
| Zod + React Hook Form | Form validation |
| @measured/puck | Visual page editor (tied to `puck_data` on Product) |
| React Leaflet | Dealer map |
| Framer Motion | Animations |

## Testing Conventions

- Tests use **Pest v4** with `pestphp/pest-plugin-laravel`
- Organized by domain under `tests/Feature/`: `Auth/`, `Cms/`, `Career/`, `Dealers/`, `Products/`
- Factories support `->published()` and `->draft()` states for `Post`/`Product`
- Translatable factory fields pass both locales: `['title' => ['en' => 'English', 'bn' => '']]`
- API tests assert the `{data, meta}` envelope shape; single-resource tests assert `{data: {...}}`
