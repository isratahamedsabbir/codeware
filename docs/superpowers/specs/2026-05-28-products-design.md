# Products Feature Design

**Date:** 2026-05-28
**Approach:** Mirror existing CMS pattern (Option A)
**Stack:** Laravel + Livewire + Flux UI (admin), REST API (Next.js frontend)

---

## Overview

A product catalog system for an agricultural products website. Products belong to dedicated product categories (separate from blog categories). The admin is managed via Livewire components (same pattern as Posts/Pages). Data is exposed via two sets of REST API endpoints: a public API for the Next.js frontend and an authenticated admin API.

---

## Database Schema

### `product_categories`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | JSON | Translatable EN/BN via Spatie Translatable |
| `slug` | string unique | Auto-generated from `name.en` on save |
| `description` | JSON nullable | Translatable EN/BN |
| `icon` | string nullable | Icon identifier for Next.js (e.g. `"leaf"`) |
| `sort_order` | integer default 0 | Controls display order |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Seeded categories: Micronutrients, Fertilizers, Seeds, Mulching Film, Agricultural Tools.

### `products`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `product_category_id` | bigint FK | References `product_categories.id` |
| `name` | JSON | Translatable EN/BN |
| `slug` | string unique | Auto-generated from `name.en` on save |
| `excerpt` | JSON nullable | Short description, translatable |
| `description` | JSON nullable | Long-form content, translatable |
| `specifications` | JSON nullable | Array of `{label: {en, bn}, value: {en, bn}}` |
| `benefits` | JSON nullable | `{en: ["...", ...], bn: ["...", ...]}` |
| `usage_instructions` | JSON nullable | Translatable EN/BN |
| `featured_image` | string nullable | URL selected from media library |
| `datasheet_url` | string nullable | PDF URL selected from media library |
| `status` | enum `draft,published` | Default `draft` |
| `is_featured` | boolean default false | Highlighted on listing pages |
| `sort_order` | integer default 0 | |
| `seo_title` | JSON nullable | Translatable EN/BN |
| `seo_description` | JSON nullable | Translatable EN/BN |
| `deleted_at` | timestamp nullable | Soft deletes |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### `product_media` (pivot)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `product_id` | bigint FK | References `products.id` |
| `media_library_id` | bigint FK | References `media_library.id` |
| `sort_order` | integer default 0 | Gallery display order |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

## Models

### `App\Models\ProductCategory`
- `HasTranslations` — translatable: `name`, `description`
- `hasMany(Product::class)`
- Auto-slug on save from `name.en`

### `App\Models\Product`
- `HasTranslations` — translatable: `name`, `excerpt`, `description`, `usage_instructions`, `seo_title`, `seo_description`
- `SoftDeletes`
- `belongsTo(ProductCategory::class)`
- `belongsToMany(MediaLibrary::class, 'product_media')->withPivot('sort_order')->orderBy('sort_order')`
- Auto-slug on save from `name.en`
- Scopes: `scopePublished`, `scopeFeatured`

---

## Livewire Admin Components

### `App\Livewire\Admin\ProductCategories\Index`

Follows `Admin\Categories\Index` pattern. Table lists all product categories. Modal form fields:
- `name_en` (required), `name_bn`
- `slug` (auto-filled, editable)
- `description_en`, `description_bn`
- `icon` (text input)
- `sort_order` (number input)

### `App\Livewire\Admin\Products\Index`

Follows `Admin\Posts\Index` pattern. Table shows: name, category badge, status badge, is_featured badge.

Modal form fields:
- `name_en` (required), `name_bn`
- `slug` (auto-filled, editable)
- `product_category_id` (select)
- `excerpt_en`, `excerpt_bn`
- `description_en`, `description_bn` (textarea)
- `status` (draft/published)
- `is_featured` (checkbox/toggle)
- `sort_order`
- `featured_image` (URL, via PickerModal)
- `gallery_media_ids` (ordered array of media_library IDs, via PickerModal — multi-select)
- `specifications` (dynamic repeater: add/remove rows of `label_en`, `label_bn`, `value_en`, `value_bn`)
- `benefits` (dynamic repeater: add/remove rows of `text_en`, `text_bn`)
- `usage_instructions_en`, `usage_instructions_bn` (textarea)
- `datasheet_url` (URL, via PickerModal — PDF)
- `seo_title_en`, `seo_title_bn`
- `seo_description_en`, `seo_description_bn`

Listens to `media-selected` event from PickerModal (same as posts). Uses a `$mediaTarget` property to distinguish between `featured_image`, `gallery`, and `datasheet` picker invocations.

---

## Admin Routes

Added to `routes/admin.php`:

```php
Route::get('/products', \App\Livewire\Admin\Products\Index::class)->name('products');
Route::get('/product-categories', \App\Livewire\Admin\ProductCategories\Index::class)->name('product-categories');
```

---

## Admin Sidebar

A new **Products** navlist group added to `resources/views/layouts/admin.blade.php`:

```
Products
  ├── Products              /admin/products
  └── Product Categories    /admin/product-categories
```

---

## REST API

### Controllers

| Class | File |
|---|---|
| `Api\V1\ProductCategoryController` | `app/Http/Controllers/Api/V1/ProductCategoryController.php` |
| `Api\V1\ProductController` | `app/Http/Controllers/Api/V1/ProductController.php` |
| `Api\V1\Admin\ProductCategoryController` | `app/Http/Controllers/Api/V1/Admin/ProductCategoryController.php` |
| `Api\V1\Admin\ProductController` | `app/Http/Controllers/Api/V1/Admin/ProductController.php` |

### Public Endpoints (no auth)

#### `GET /api/v1/product-categories`
Returns all product categories ordered by `sort_order`.

Response:
```json
{
  "data": [
    {
      "id": 1,
      "name": {"en": "Fertilizers", "bn": "সার"},
      "slug": "fertilizers",
      "description": {"en": "...", "bn": "..."},
      "icon": "leaf",
      "sort_order": 0
    }
  ]
}
```

#### `GET /api/v1/products`
Returns paginated published products.

Query parameters:
- `category` — filter by category slug
- `search` — searches `name->en` and `name->bn`
- `featured` — `1` to return only `is_featured = true` products
- `per_page` — default 12

Response:
```json
{
  "data": [
    {
      "id": 1,
      "name": {"en": "...", "bn": "..."},
      "slug": "...",
      "excerpt": {"en": "...", "bn": "..."},
      "featured_image": "https://...",
      "is_featured": true,
      "sort_order": 0,
      "category": {"id": 1, "name": {...}, "slug": "fertilizers"},
      "datasheet_url": "https://..."
    }
  ],
  "meta": { "current_page": 1, "last_page": 3, "per_page": 12, "total": 30 },
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." }
}
```

#### `GET /api/v1/products/{slug}`
Returns full product detail.

Response adds to listing fields:
```json
{
  "data": {
    "description": {"en": "...", "bn": "..."},
    "specifications": [
      {"label": {"en": "NPK Ratio", "bn": "..."}, "value": {"en": "10-20-10", "bn": "..."}}
    ],
    "benefits": {"en": ["...", "..."], "bn": ["...", "..."]},
    "usage_instructions": {"en": "...", "bn": "..."},
    "seo_title": {"en": "...", "bn": "..."},
    "seo_description": {"en": "...", "bn": "..."},
    "gallery": [
      {"id": 1, "url": "https://...", "alt": "...", "sort_order": 0}
    ],
    "related_products": [
      { "id": 2, "name": {...}, "slug": "...", "featured_image": "...", "excerpt": {...} }
    ]
  }
}
```

`related_products`: up to 4 published products in the same category, excluding the current product, ordered by `sort_order`.

---

### Admin Endpoints (Sanctum auth, `admin` middleware)

#### Product Categories

| Method | Endpoint | Action |
|---|---|---|
| `GET` | `/api/v1/admin/product-categories` | List all |
| `POST` | `/api/v1/admin/product-categories` | Create |
| `PUT` | `/api/v1/admin/product-categories/{id}` | Update |
| `DELETE` | `/api/v1/admin/product-categories/{id}` | Delete |

#### Products

| Method | Endpoint | Action |
|---|---|---|
| `GET` | `/api/v1/admin/products` | List all (draft + published) |
| `POST` | `/api/v1/admin/products` | Create |
| `PUT` | `/api/v1/admin/products/{id}` | Update |
| `DELETE` | `/api/v1/admin/products/{id}` | Soft delete |

**Gallery sync:** `POST` and `PUT` accept a `media_ids` field — an ordered array of `media_library` IDs. The controller syncs `product_media` using `syncWithPivotValues`, deriving `sort_order` from array index position.

---

## API Routes

Added to `routes/api.php` under the existing `v1` prefix group:

```php
// Public
Route::get('/product-categories', [ProductCategoryController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// Admin
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::apiResource('product-categories', Admin\ProductCategoryController::class)->except(['show']);
    Route::apiResource('products', Admin\ProductController::class)->except(['show']);
});
```

---

## Out of Scope

- Puck visual editor for product descriptions (descriptions are plain translatable text for now)
- Product reviews / ratings
- Inventory / stock tracking
- Price fields
