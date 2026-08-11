# Page Seeder + API-First Frontend Design

**Date:** 2026-05-29
**Scope:** Laravel `PageSeeder` + frontend `puck-data.ts` API-first with JSON fallback

---

## Overview

Two focused changes:

1. **Laravel** — a `PageSeeder` that reads the 8 page JSON files from the Next.js frontend and creates published `Page` records in the database.
2. **Frontend** — `puck-data.ts` updated to fetch page data from the Laravel API first, falling back to local JSON files if the API is unavailable or returns 404.

---

## Part 1 — PageSeeder (Laravel)

### File
`database/seeders/PageSeeder.php`

### Behavior
- Reads each of the 8 JSON files from `../agrosal-frontend/src/data/pages/` (relative to the Laravel project root)
- For each file, creates or updates (upsert on `slug`) a `Page` record
- Skips a page if the JSON file is not found (graceful — never throws)
- All pages seeded as `status = published`, owned by the admin user (`email = admin@agrosal.com`)
- `DatabaseSeeder` calls `PageSeeder` after existing seeders

### Field mapping

| JSON field | Page column | Notes |
|---|---|---|
| `title` | `title` | `['en' => $json['title'], 'bn' => '']` |
| `description` | `seo_description` | `['en' => $json['description'], 'bn' => '']` |
| `root` + `content` | `puck_data` | `['root' => $json['root'], 'content' => $json['content']]` |
| filename (without `.json`) | `slug` | e.g. `home.json` → slug `home` |
| — | `template` | hardcoded `'puck'` |
| — | `sort_order` | assigned by position (0–7) |
| — | `status` | hardcoded `'published'` |

### Pages seeded

| File | slug | Title (en) |
|---|---|---|
| `home.json` | `home` | Home - AgroSAL |
| `about.json` | `about` | About Us - AgroSAL |
| `career.json` | `career` | Career - AgroSAL |
| `contact.json` | `contact` | Contact Us - AgroSAL |
| `dealers.json` | `dealers` | Dealer & Distribution - AgroSAL |
| `initiatives.json` | `initiatives` | Our Initiatives - AgroSAL |
| `media.json` | `media` | Media Center - AgroSAL |
| `products.json` | `products` | Products - AgroSAL |

### DatabaseSeeder change
Add `$this->call(PageSeeder::class);` after `ProductCategorySeeder`.

---

## Part 2 — Frontend API-first page data (Next.js)

### Environment variable
Add `LARAVEL_API_URL` to `.env.local` (server-side only, not `NEXT_PUBLIC_`):
```
LARAVEL_API_URL=http://localhost:8000
```

### Updated `src/lib/puck-data.ts`

Replace the current file-only implementation with:

**`fetchPageData(slug: string[] | undefined): Promise<PageApiResult | null>`**
- Builds slug string: `undefined` or `[]` → `"home"`, else `slug.join("/")`
- Calls `GET {LARAVEL_API_URL}/api/v1/pages/{slugStr}?locale=en`
- On success (2xx): returns `{ puckData: data.puck_data, title: data.title, description: data.seo_description }`
- On failure (404, network error, missing env var): falls back to `readPageDataFromFile(slug)` which is the existing file-reading logic

**`listPageSlugsFromApi(): Promise<string[][]>`**
- Calls `GET {LARAVEL_API_URL}/api/v1/pages`
- On success: maps `data[].slug` → filters out `"home"` (homepage handled separately) → splits each slug by `/`
- On failure: falls back to `listPageSlugsFromFiles()` (existing filesystem logic)

**Keep unchanged:**
- `writePageData(slug, data)` — still writes to local JSON (used by Puck editor)
- `slugToFileName(slug)` — still used by fallback and write paths

### Updated `src/app/[...slug]/page.tsx`
- Change `readPageData(slug)` → `await fetchPageData(slug)` (async)
- Receive `{ puckData, title, description }` instead of raw JSON
- Pass `puckData` to `PuckRenderer`
- Use `title`/`description` in `generateMetadata`
- `generateStaticParams` calls `await listPageSlugsFromApi()`

### Updated `src/app/page.tsx` (homepage)
- Change `readPageData([])` → `await fetchPageData([])`
- Pass `puckData` to `PuckRenderer`

### `src/app/api/puck/[...slug]/route.ts` — no change
The Puck editor route reads/writes local JSON files directly. It does not go through the API. This is intentional — the editor saves back to local files, not to the Laravel API (that's a separate admin flow).

---

## Data flow

```
Browser requests /about
  └─ Next.js [slug]/page.tsx
      └─ fetchPageData(['about'])
          ├─ try: GET /api/v1/pages/about → { data: { puck_data, title, ... } }
          │   └─ return { puckData: data.puck_data, title, description }
          └─ catch/404: readPageDataFromFile(['about'])
              └─ return { puckData: { root, content }, title, description }
                  └─ PuckRenderer receives puckData
```

---

## Error handling

- API unreachable (ECONNREFUSED, timeout): silently falls back to JSON file
- API returns 404 (page not in DB): falls back to JSON file
- JSON file also missing: returns `null` → `notFound()` (404 page)
- Missing `LARAVEL_API_URL` env var: skip API call, go straight to file fallback

---

## File changes summary

| File | Change |
|---|---|
| `database/seeders/PageSeeder.php` | New |
| `database/seeders/DatabaseSeeder.php` | Add `PageSeeder` call |
| `agrosal-frontend/src/lib/puck-data.ts` | Replace with API-first + fallback |
| `agrosal-frontend/src/app/[...slug]/page.tsx` | Use async `fetchPageData` |
| `agrosal-frontend/src/app/page.tsx` | Use async `fetchPageData` |
| `agrosal-frontend/.env.local` | Add `LARAVEL_API_URL` |
