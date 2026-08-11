# Dealer Locator Feature Design

**Date:** 2026-05-28
**Approach:** Option A — Single `dealers` table with category pivot
**Stack:** Laravel + Livewire + Flux UI (admin), REST API (Next.js frontend)

---

## Overview

A dealer locator system for Bangladesh. Dealers are managed via Livewire admin CRUD. The public REST API exposes active dealers with filtering by district, upazila, product category, text search, and geographic proximity (Haversine). No admin REST API — only Livewire admin and public endpoints.

---

## Database Schema

### `dealers`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | JSON | Translatable EN/BN via Spatie Translatable |
| `slug` | string unique | Auto-generated from `name.en` on save |
| `address` | JSON | Translatable EN/BN |
| `district` | string | e.g. `"Dhaka"` — used for filtering |
| `upazila` | string nullable | Sub-district for finer filtering |
| `phone` | string | Contact number |
| `email` | string nullable | |
| `latitude` | decimal(10,7) nullable | For map pin and nearby calculation |
| `longitude` | decimal(10,7) nullable | |
| `status` | enum `active,inactive` default `active` | |
| `sort_order` | unsignedSmallInteger default 0 | |
| `deleted_at` | timestamp nullable | Soft deletes |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `dealer_product_categories` (pivot)

| Column | Type | Notes |
|---|---|---|
| `dealer_id` | bigint FK | References `dealers.id`, cascadeOnDelete |
| `product_category_id` | bigint FK | References `product_categories.id`, cascadeOnDelete |

No `id`, no `timestamps` — bare pivot table.

---

## Models

### `App\Models\Dealer`
- `HasTranslations` — translatable: `name`, `address`
- `SoftDeletes`
- `HasFactory`
- Auto-slug on save from `name.en`
- `categories(): BelongsToMany` → `ProductCategory` via `dealer_product_categories`
- `scopeActive(Builder $query)` — where status = active

---

## Livewire Admin Component

### `App\Livewire\Admin\Dealers\Index`

Follows `Admin\Products\Index` pattern. Single-modal form (no tabs needed).

**Table columns:** Name (EN), District, Upazila, Status badge, Actions (Edit, Delete)

**Modal form fields:**
- `name_en` (required), `name_bn` — locale tab switcher
- `slug` — auto-filled, editable
- `address_en`, `address_bn` — locale tab switcher, textarea
- `district` — text input
- `upazila` — text input (optional)
- `phone` — text input
- `email` — text input (optional)
- `latitude`, `longitude` — two decimal number inputs side by side
- `status` — select (active / inactive)
- `sort_order` — number input
- `selectedCategories` — checkbox list rendered from all `ProductCategory` records

**CRUD methods:** `openCreate`, `openEdit(int $id)`, `save`, `confirmDelete(int $id)`, `delete`

**Render:** paginated with search on `name->en`, `name->bn`, district; `withCount('categories')`; `orderBy('sort_order')`; layout `layouts.admin` with title `'Dealers'`

---

## Admin Sidebar

New **"Dealer Network"** navlist group added to `resources/views/layouts/admin.blade.php`:

```
Dealer Network
  └── Dealers    /admin/dealers
```

New route in `routes/admin.php`:
```php
Route::get('/dealers', \App\Livewire\Admin\Dealers\Index::class)->name('dealers');
```

---

## Public REST API

### Controllers
- `App\Http\Controllers\Api\V1\DealerController`

### Endpoints

#### `GET /api/v1/dealers`

Returns paginated active dealers.

**Query parameters:**
| Param | Type | Description |
|---|---|---|
| `district` | string | Filter by district (case-insensitive LIKE) |
| `upazila` | string | Filter by upazila (case-insensitive LIKE) |
| `category` | string | Filter by product category slug |
| `search` | string | Searches `name->en`, `name->bn`, `district` |
| `lat` | float | Latitude for proximity search |
| `lng` | float | Longitude for proximity search |
| `radius` | float | Radius in km (default 50, max 200) |
| `per_page` | int | Default 15, max 100 |
| `locale` | string | `en` or `bn`, default `en` |

**Nearby search:** When `lat` and `lng` are provided, uses the Haversine formula as a raw SQL select to compute distance in km, then filters to `radius` km and orders by distance ascending. Other sort orders are ignored when proximity search is active.

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Dhaka Agro Store",
      "slug": "dhaka-agro-store",
      "address": "123 Farmgate, Dhaka",
      "district": "Dhaka",
      "upazila": "Tejgaon",
      "phone": "+8801700000000",
      "email": "dealer@example.com",
      "latitude": 23.7465,
      "longitude": 90.3760,
      "status": "active",
      "sort_order": 0,
      "distance_km": 2.4,
      "categories": [
        {"id": 1, "name": "Fertilizers", "slug": "fertilizers", "icon": "sparkles"}
      ]
    }
  ],
  "meta": {
    "current_page": 1, "last_page": 3, "per_page": 15, "total": 42
  }
}
```

`distance_km` is present only when `lat`+`lng` params are supplied; `null` otherwise.

#### `GET /api/v1/dealers/{slug}`

Returns full dealer detail.

Same fields as listing response. Always includes `categories`. `distance_km` is absent (single dealer, no reference point).

---

## Routes

Added to `routes/api.php` under `prefix('v1')`:

```php
Route::get('/dealers', [DealerController::class, 'index'])->name('dealers.index');
Route::get('/dealers/{slug}', [DealerController::class, 'show'])->name('dealers.show');
```

---

## Out of Scope

- Admin REST API (not required for now)
- Auto-geocoding from address (lat/lng entered manually)
- Google Maps SDK integration (handled by Next.js frontend)
- Dealer registration / self-service portal
