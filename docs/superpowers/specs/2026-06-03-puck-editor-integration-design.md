# Puck Visual Editor Integration — AgroSAL

**Date:** 2026-06-03  
**Status:** Approved  
**Scope:** Pages, Posts, Products

---

## Overview

Integrate the `@measured/puck` visual page builder into AgroSAL so that admins can build and edit the layout of Pages, Posts, and Products using a drag-and-drop editor. The design is based on the proven pattern from the CrystalValet project (which is in `d:\herd\crystalvalet-frontend` for reference).

The agrosal-frontend already has Puck `^0.20.2` installed and 30+ components built. The Laravel backend already validates and returns `puck_data` in all relevant API endpoints. The primary work is:
1. Adding missing DB columns
2. Creating entity-specific Puck configs
3. Wiring the frontend editor to save via the Laravel API
4. Adding bilingual field support to all Puck components
5. Adding "Edit in Puck" buttons to the Livewire admin forms

---

## Section 1 — Database (Laravel backend)

Three migration files to add the missing `puck_data` JSON column:

| Table | Migration file | Column |
|-------|---------------|--------|
| `pages` | `YYYY_MM_DD_add_puck_data_to_pages_table.php` | `puck_data` JSON nullable |
| `posts` | `YYYY_MM_DD_add_puck_data_to_posts_table.php` | `puck_data` JSON nullable |
| `products` | `YYYY_MM_DD_add_puck_data_to_products_table.php` | `puck_data` JSON nullable |

No other schema changes. The models, controllers, and API responses are already correct — `puck_data` is in `$fillable`, cast to `array`, accepted in validation, and returned in `show()` responses for all three entities.

---

## Section 2 — Admin Auth Handoff

### Sanctum token creation — server-side, no API roundtrip

The Livewire action creates the Sanctum personal access token directly on the server (no HTTP call required — the admin is already authenticated via Laravel session). This requires the `User` model to have the `Laravel\Sanctum\HasApiTokens` trait. Verify this is present before implementation.

### Livewire Form components — "Edit in Puck" button

The Form Livewire components for Pages, Posts, and Products each get an "Edit in Puck" button. When clicked:

1. Livewire action creates a 2-hour Sanctum token directly via `auth()->user()->createToken(...)`
2. Dispatches a browser event `open-puck-editor` with the full editor URL
3. Alpine.js listener on the Blade template opens the URL in a new tab

The Livewire action:
```php
public function openPuckEditor(): void
{
    $token = auth()->user()
        ->createToken('puck-editor', ['*'], now()->addHours(2))
        ->plainTextToken;

    $url = config('services.frontend_url')
        . '/puck/edit/' . $this->entityType
        . '/' . $this->recordId
        . '?token=' . $token;

    $this->dispatch('open-puck-editor', url: $url);
}
```

The Blade button:
```blade
<div x-on:open-puck-editor.window="window.open($event.detail.url, '_blank')">
    <flux:button wire:click="openPuckEditor" icon="pencil-square">
        Edit in Puck
    </flux:button>
</div>
```

`services.frontend_url` must be added to `config/services.php` and `.env` (`FRONTEND_URL=http://localhost:3000`).

No separate API controller or route is needed for token generation.

Affected Livewire form components:
- `app/Livewire/Admin/Pages/Form.php` + `form.blade.php`
- `app/Livewire/Admin/Posts/Form.php` + `form.blade.php`
- `app/Livewire/Admin/Products/Form.php` + `form.blade.php`

---

## Section 3 — Frontend Editor Route & Save Flow

### New route

`agrosal-frontend/src/app/puck/edit/[entity]/[id]/page.tsx`

Replaces the current `/puck/edit/[[...slug]]` pattern. The old route can be removed.

### Editor page behaviour

1. Read `entity` (`page` | `post` | `product`) and `id` from route params
2. Read `?token` query param → store in `sessionStorage` → remove from URL via `history.replaceState`
3. Fetch existing `puck_data` from admin API: `GET /api/v1/admin/{entity}s/{id}` with `Authorization: Bearer {token}`
4. Select the matching Puck config:
   - `page` → `pageConfig` from `lib/puck/page-config.tsx`
   - `post` → `postConfig` from `lib/puck/post-config.tsx`
   - `product` → `productConfig` from `lib/puck/product-config.tsx`
5. Render `<Puck config={config} data={puckData} onPublish={handleSave} />`
6. On save: `PUT /api/v1/admin/{entity}s/{id}` with body `{ puck_data: data }`

### API path mapping

| Entity | Fetch | Save |
|--------|-------|------|
| `page` | `GET /api/v1/admin/pages/{id}` | `PUT /api/v1/admin/pages/{id}` |
| `post` | `GET /api/v1/admin/posts/{id}` | `PUT /api/v1/admin/posts/{id}` |
| `product` | `GET /api/v1/admin/products/{id}` | `PUT /api/v1/admin/products/{id}` |

### Shared API client

`agrosal-frontend/src/lib/api/client.ts` — an Axios instance that reads the Bearer token from `sessionStorage`. Reuse/create once and import from the editor page.

---

## Section 4 — Three Puck Configs

All configs live in `agrosal-frontend/src/lib/puck/`.

### `page-config.tsx` — for Pages

Pages include: Home, About, Contact, Career, Initiatives, Dealers, Media, and any other public-facing static pages.

Components:
- `HeroSection` — banner with title, subtitle, image, CTA
- `HomeSlider` — featured carousel (home only)
- `FeaturesGrid` — icon-based feature highlights
- `StatsSection` — key statistics (years of operation, farmers served, etc.)
- `TimelineSection` — company milestones
- `TeamSection` — team members
- `TestimonialsBlock` — farmer testimonials with auto-play
- `DealersSection` — dealer map with district/upazila filter
- `ContactSection` — contact info with map
- `NewsSection` — latest blog posts (fetches from API)
- `JobsSection` — open job listings (fetches from API)
- `InitiativesSection` — CSR/Buy Back/Afforestation initiatives
- `ProductHighlights` — featured products grid
- `CTASection` — call-to-action blocks
- `InfoCards` — icon-based info cards
- `VideoSection` — video category listing
- `WeatherWidget` — district-based weather forecast
- `TwoColumn` — two-column text/image layout
- `ContentBlock` — rich text block
- `ImageBlock` — image with caption

### `post-config.tsx` — for Blog Posts

Posts are article-style content; the Puck layout replaces the rich-text `content` field.

Components:
- `ContentBlock` — main article body (rich text)
- `ImageBlock` — inline images with caption
- `VideoSection` — embedded video
- `CTASection` — end-of-article call to action
- `TestimonialsBlock` — related farmer quotes
- `StatsSection` — data/statistics callout

### `product-config.tsx` — for Product Detail Pages

Product pages showcase a single product with its details, specs, and purchase pathways.

Components:
- `HeroSection` — product hero (name, featured image, CTA)
- `FeaturesGrid` — product features/benefits
- `SpecsTable` — technical specifications
- `UsageSection` — usage instructions (how to apply)
- `TestimonialsBlock` — farmer testimonials for this product
- `CTASection` — buy/enquire CTA
- `DealersSection` — where to buy
- `VideoSection` — product demo/explainer video
- `ProductHighlights` — related products
- `ImageGallery` — product image gallery

---

## Section 5 — Bilingual Field Pattern

All Puck component text fields are updated to use `type: 'object'` with `en` and `bn` sub-fields.

### Field definition (in config files)

```typescript
title: {
  type: 'object',
  label: 'Title',
  objectFields: {
    en: { type: 'text', label: 'Title (English)' },
    bn: { type: 'text', label: 'Title (Bengali)' },
  },
}
```

For longer text (descriptions, body text):
```typescript
description: {
  type: 'object',
  label: 'Description',
  objectFields: {
    en: { type: 'textarea', label: 'Description (English)' },
    bn: { type: 'textarea', label: 'Description (Bengali)' },
  },
}
```

### Shared translation util

`agrosal-frontend/src/lib/t.ts`:
```typescript
type BilingualField = string | { en: string; bn: string }

export function t(field: BilingualField | undefined, locale: string): string {
  if (!field) return ''
  if (typeof field === 'string') return field
  return field[locale as 'en' | 'bn'] || field.en || ''
}
```

### Component render pattern

```typescript
import { t } from '@/lib/t'
import { useLanguage } from '@/contexts/language-context'

function HeroSectionComponent({ title, subtitle, ... }: HeroSectionProps) {
  const { locale } = useLanguage()
  return (
    <section>
      <h1>{t(title, locale)}</h1>
      <p>{t(subtitle, locale)}</p>
    </section>
  )
}
```

### Props interface pattern

```typescript
type BilingualField = string | { en: string; bn: string }

interface HeroSectionProps {
  title: BilingualField
  subtitle?: BilingualField
  description?: BilingualField
  ctaText?: BilingualField
  ctaLink?: string        // URLs stay as plain strings
  backgroundImage?: string // media paths stay as plain strings
}
```

### Fields that do NOT need bilingual treatment

- URLs, slugs, hrefs (`ctaLink`, `href`)
- Image/file paths (`src`, `backgroundImage`, `featuredImage`)
- Numeric values (`columns`, `overlayOpacity`, `autoplayInterval`)
- Boolean flags (`autoplay`, `showArrows`, `isPopular`)
- Select/enum values (`layout`, `alignment`, `backgroundType`)

Only human-readable text that appears on the page needs `{ en, bn }` treatment.

---

## Section 6 — Frontend Rendering

The public-facing rendering is already partially wired:

- `agrosal-frontend/src/lib/puck-data.ts` fetches `puck_data` from the public API (`GET /api/v1/pages/{slug}`)
- `agrosal-frontend/src/components/puck/PuckRenderer.tsx` renders the Puck JSON

**What changes:**
- The `PuckRenderer` must import the correct config depending on the entity type (page vs post vs product)
- For post detail (`/posts/[slug]`): if `puck_data` is present, render with `postConfig`; otherwise fall back to rendering `content` HTML
- For product detail (`/products/[slug]`): if `puck_data` is present, render with `productConfig`; otherwise render the existing structured fields (description, specs, usage_instructions, etc.)
- For pages (`/[...slug]`): render with `pageConfig` (already the primary path)

The locale is available from `LanguageContext` — every component calls `useLanguage()` and uses `t(field, locale)` to pick the right text.

---

## Out of Scope

- Page revision history UI (the backend auto-creates revisions on page `content` changes; not exposing this in the Puck editor for now)
- Drag-and-drop media library picker in Puck fields (image URLs are entered manually for now; can be added later)
- Real-time collaborative editing
- Any changes to the currently approved public-facing component visual design (styles remain identical)

---

## File Change Summary

### Laravel backend (`d:\herd\agrosal`)

| File | Action |
|------|--------|
| `database/migrations/*_add_puck_data_to_pages_table.php` | Create |
| `database/migrations/*_add_puck_data_to_posts_table.php` | Create |
| `database/migrations/*_add_puck_data_to_products_table.php` | Create |
| `app/Models/User.php` | Verify `HasApiTokens` trait present |
| `config/services.php` | Add `frontend_url` key |
| `.env` / `.env.example` | Add `FRONTEND_URL` |
| `app/Livewire/Admin/Pages/Form.php` | Add `openPuckEditor()` action |
| `app/Livewire/Admin/Posts/Form.php` | Add `openPuckEditor()` action |
| `app/Livewire/Admin/Products/Form.php` | Add `openPuckEditor()` action |
| `resources/views/livewire/admin/pages/form.blade.php` | Add "Edit in Puck" button |
| `resources/views/livewire/admin/posts/form.blade.php` | Add "Edit in Puck" button |
| `resources/views/livewire/admin/products/form.blade.php` | Add "Edit in Puck" button |

### agrosal-frontend (`d:\herd\agrosal\agrosal-frontend`)

| File | Action |
|------|--------|
| `src/lib/t.ts` | Create — bilingual translation util |
| `src/lib/api/client.ts` | Create — Axios client reading token from sessionStorage |
| `src/lib/puck/page-config.tsx` | Create — Puck config for pages |
| `src/lib/puck/post-config.tsx` | Create — Puck config for posts |
| `src/lib/puck/product-config.tsx` | Create — Puck config for products |
| `src/app/puck/edit/[entity]/[id]/page.tsx` | Create — unified editor page |
| `src/app/puck/edit/[[...slug]]/page.tsx` | Remove (replaced) |
| `src/components/puck/*.tsx` (all components) | Update — bilingual fields + `t()` in render |
| `src/components/puck/PuckRenderer.tsx` | Update — accept config as prop or pick by entity type |
| `src/app/posts/[slug]/page.tsx` | Update — use `postConfig` + `puck_data` fallback |
| `src/app/products/[slug]/page.tsx` | Update — use `productConfig` + `puck_data` fallback |
