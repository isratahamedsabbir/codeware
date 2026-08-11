# Admin Panel Redesign — Design Spec
**Date:** 2026-05-29
**Status:** Approved

## Overview

Full redesign of the AgroSAL admin panel to feel modern, professional, and SaaS-grade. Direction: dark & sleek — dark sidebar, light content area, brand blue (#1e7bc4) as primary accent, brand green (#7cc242) as secondary accent.

---

## 1. Layout Structure

Two-column fixed layout:

- **Sidebar** — `w-64`, fixed height, `bg-zinc-950`
- **Content area** — `flex-1`, scrollable, `bg-zinc-50`
  - Sticky top bar (`h-14`, `bg-white`, `border-b border-zinc-200`) — page title + user avatar
  - Main content below with `p-6` padding

### Sidebar Tokens

| Element | Classes |
|---|---|
| Background | `bg-zinc-950` |
| Logo area | `h-16 px-4 border-b border-zinc-800` |
| Group label | `text-xs uppercase tracking-wider text-zinc-500 px-3 mb-1 mt-4` |
| Nav item (default) | `text-zinc-400 hover:bg-zinc-800/60 hover:text-zinc-100 rounded-lg` |
| Nav item (active) | `bg-blue-600/15 text-blue-400 border-l-2 border-blue-500 rounded-lg` |
| Bottom user strip | `border-t border-zinc-800 p-3` — avatar + name + logout icon |

### Top Bar

- Height: `h-14`, `bg-white`, `border-b border-zinc-200`, sticky
- Left: current page title (`text-sm font-semibold text-zinc-700`)
- Right: user avatar with initials, dropdown for profile/logout

---

## 2. Dashboard

### Greeting Header

```
Good morning, Admin                    Thursday, 29 May 2026
```

- Title: `text-2xl font-bold text-zinc-900`
- Date: `text-sm text-zinc-500`

### Stat Cards Grid

6 cards in `grid grid-cols-2 lg:grid-cols-3 gap-4`:

| Stat | Icon color | Metric source |
|---|---|---|
| Products | Blue | `Product::published()->count()` |
| Product Categories | Green | `ProductCategory::count()` |
| Dealers | Blue | `Dealer::count()` |
| Job Applications | Green | `Application::count()` |
| Published Posts | Blue | `Post::published()->count()` |
| Published Pages | Green | `Page::published()->count()` |

**Card anatomy:**
- `bg-white rounded-xl shadow-sm border border-zinc-100 p-5`
- Icon: `size-10 rounded-lg` — `bg-blue-50` or `bg-green-50` with brand-colored icon
- Metric: `text-3xl font-bold text-zinc-900`
- Label: `text-sm text-zinc-500`
- Trend: `text-xs` — green arrow + count for positive change, neutral dash for unchanged

### Quick Actions Row

3 shortcut buttons below the grid:
- **+ New Product** → `route('admin.products')`
- **+ New Post** → `route('admin.posts')`
- **View Applications** → `route('admin.applications')`

---

## 3. Page Structure (all admin pages)

Every page uses this consistent layout:

### Page Header

```html
<div class="flex items-center justify-between mb-6">
  <div>
    <h1><!-- Page Title --></h1>
    <p><!-- Short subtitle --></p>
  </div>
  <button><!-- Primary action --></button>
</div>
```

- Title: `text-2xl font-bold text-zinc-900`
- Subtitle: `text-sm text-zinc-500`
- Primary button: `bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2`

### Filter / Search Bar

- `bg-white rounded-xl border border-zinc-200 p-3 mb-4`
- Search input left-aligned, filter dropdowns right-aligned

### Content Card

- `bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden`
- Table: no outer cell borders, `divide-y divide-zinc-100`
- Row hover: `hover:bg-zinc-50`
- Header row: `bg-zinc-50 text-xs uppercase tracking-wider text-zinc-500`

### Status Badges

| Status | Classes |
|---|---|
| Published | `bg-green-100 text-green-700 rounded-full px-2 py-0.5 text-xs font-medium` |
| Draft | `bg-zinc-100 text-zinc-500 rounded-full px-2 py-0.5 text-xs font-medium` |
| Featured | `bg-blue-100 text-blue-700 rounded-full px-2 py-0.5 text-xs font-medium` |

### Action Buttons (per row)

- Icon-only: `text-zinc-400 hover:text-blue-600` (edit), `text-zinc-400 hover:text-red-500` (delete)
- Size: `size-8 rounded-md hover:bg-zinc-100`

### Pagination

- Right-aligned, minimal style
- `text-sm text-zinc-600`

---

## 4. Files to Change

| File | Change |
|---|---|
| `resources/views/layouts/admin.blade.php` | Full sidebar + top bar redesign |
| `resources/views/livewire/admin/dashboard.blade.php` | Stat cards + quick actions |
| `app/Livewire/Admin/Dashboard.php` | Load stats from DB |
| `resources/css/app.css` | Admin-scoped utility tokens |

All Livewire component views (products, posts, pages, etc.) get the new page header + filter bar + content card structure applied uniformly — no changes to PHP component logic, only blade view markup.

---

## 5. Constraints

- No new packages — use only Flux UI + Tailwind v4 utilities already installed
- Dark mode: sidebar is always dark; content area respects existing Flux dark mode toggle
- All changes are in blade views and CSS only — no Livewire component logic changes except `Dashboard.php` for stat queries
- Existing `wire:navigate.hover` on sidebar links is preserved
