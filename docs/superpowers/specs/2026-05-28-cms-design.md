# CMS Feature Design — Agrosal

**Date:** 2026-05-28
**Approach:** Port backend from crystalvalet (stripped of domain-specific fields) + full Flux-based admin UI redesign with Puck visual editor
**Source reference:** `d:\herd\agrosal\crystalvalet\`

---

## Overview

Add a headless CMS to the agrosal Laravel API project. A Livewire + Flux admin panel manages content metadata (title, slug, status, SEO, tags); the **Puck visual editor** (a separate Next.js app) handles the actual content editing via a new-tab flow. A versioned public REST API serves the Next.js frontend. Admin write operations go through authenticated API endpoints consumed directly by Puck.

All CMS modules: Pages, Posts, Categories, Tags, Media Library, Settings (including Layout header/footer).

---

## Architecture

```
Livewire Admin Panel (/admin/*)
  │  generates Sanctum token + URL
  │  opens new tab
  ▼
Puck Editor (Next.js, CMS_EDITOR_BASE_URL)
  /builder?page=ID&token=TOKEN       ← for pages
  /post-builder?post=ID&token=TOKEN  ← for posts
  /editor?mode=layout&type=header&token=TOKEN  ← for layout
  │  calls API directly with token
  ▼
Laravel API (/api/v1/...)
  ├── Public: GET posts, pages, settings/public, layout
  └── Auth (Sanctum): POST/PUT admin/pages, admin/posts,
                       layout/header, layout/footer, media/upload
```

Content is stored as **Puck JSON** (not HTML). The Next.js frontend renders it using the Puck render engine.

---

## 1. Database Schema

Nine migrations, all new files in `database/migrations/`.

### `categories`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| name | string | |
| slug | string | unique |
| description | text | nullable |
| timestamps | | |

### `tags`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| name | string | |
| slug | string | unique |
| timestamps | | |

### `posts`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| user_id | foreignId | FK → users |
| category_id | foreignId | nullable, FK → categories |
| title | string | |
| slug | string | unique |
| excerpt | text | nullable |
| content | longText | nullable — **stores Puck JSON** |
| featured_image | string | nullable, full URL string (not a FK) |
| status | enum | draft, published |
| published_at | timestamp | nullable |
| reading_time | unsignedSmallInteger | nullable, minutes |
| seo_title | string | nullable |
| seo_description | text | nullable |
| deleted_at | timestamp | soft deletes |
| timestamps | | |

### `post_tag` (pivot)
| Column | Type |
|---|---|
| post_id | foreignId |
| tag_id | foreignId |

### `pages`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| user_id | foreignId | FK → users |
| title | string | |
| slug | string | unique |
| content | longText | nullable — **stores Puck JSON** |
| status | enum | draft, published |
| template | string | default: 'puck' |
| sort_order | unsignedInteger | default 0 |
| seo_title | string | nullable |
| seo_description | text | nullable |
| deleted_at | timestamp | soft deletes |
| timestamps | | |

> No `site_id` or `location_prefix_id` — those are crystalvalet domain-specific.

### `page_revisions`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| page_id | foreignId | FK → pages |
| user_id | foreignId | FK → users |
| content | longText | Puck JSON snapshot at save time |
| timestamps | | |

### `media_library`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| filename | string | stored filename on disk |
| original_name | string | original upload name |
| path | string | relative path under storage/app/public |
| disk | string | default: public |
| mime_type | string | |
| size | unsignedBigInteger | bytes |
| alt_text | string | nullable |
| timestamps | | |

### `settings`
| Column | Type | Notes |
|---|---|---|
| id | bigIncrements | |
| key | string | unique |
| value | text | nullable |
| type | string | string, boolean, integer, json |
| group | string | nullable, for UI grouping |
| is_public | boolean | default false — exposes via `/api/v1/settings/public` |
| timestamps | | |

Seeded layout keys: `header_content` (type: json, is_public: false) and `footer_content` (type: json, is_public: false) — edited via the Puck layout editor.

### Users table addition
One migration to add `is_admin` (boolean, default false) to the existing `users` table. Used by `AdminMiddleware`.

---

## 2. Models

All models in `app/Models/`.

### `Category`
- `hasMany(Post::class)`
- Fillable: name, slug, description
- Auto-generates slug from name when slug field is empty (does not overwrite manually set slugs)

### `Tag`
- `belongsToMany(Post::class)`
- Fillable: name, slug

### `Post`
- `belongsTo(User::class)`, `belongsTo(Category::class)`, `belongsToMany(Tag::class)`
- Casts: `content` → `array` (Puck JSON)
- Scopes: `scopePublished()`, `scopeDraft()`
- `reading_time` computed from word count of content and stored on save
- SoftDeletes

### `Page`
- `belongsTo(User::class)`, `hasMany(PageRevision::class)`
- Casts: `content` → `array` (Puck JSON)
- Scopes: `scopePublished()`, `scopeDraft()`
- Automatically creates a `PageRevision` in the `saving` observer
- SoftDeletes

### `PageRevision`
- `belongsTo(Page::class)`, `belongsTo(User::class)`
- Never updated in place; append-only

### `MediaLibrary`
- Standalone, no relations
- `url` accessor returns `Storage::url($this->path)`

### `Setting`
- Static helpers: `Setting::get('key', $default)` and `Setting::set('key', $value)`
- Internally uses cache; cache is cleared on `Setting::set()`

---

## 3. Puck Editor Integration (Sanctum)

Puck is a separate Next.js app. The Livewire admin generates a short-lived Sanctum token and opens the Puck editor in a new browser tab. Puck calls the Laravel API directly using that token.

### Prerequisites
- Install `laravel/sanctum` via Composer
- Add `HasApiTokens` trait to the `User` model
- Run Sanctum migrations

### Config
New file `config/cms.php`:
```php
return [
    'editor_base_url' => env('CMS_EDITOR_BASE_URL', 'http://localhost:3000'),
];
```

Add to `.env`:
```
CMS_EDITOR_BASE_URL=http://localhost:3000
```

### Token + URL generation (in Livewire components)

**Pages:**
```php
public function editUrl(int $pageId): string
{
    $token = auth()->user()->createToken('puck-builder')->plainTextToken;
    return config('cms.editor_base_url') . "/builder?page={$pageId}&token={$token}";
}

public function createUrl(): string
{
    $token = auth()->user()->createToken('puck-builder')->plainTextToken;
    return config('cms.editor_base_url') . "/builder?page=new&token={$token}";
}
```

**Posts:**
```php
// Same pattern but /post-builder route
```

**Layout:**
```php
public function layoutEditorUrl(string $type): string // 'header' or 'footer'
{
    $token = auth()->user()->createToken('puck-builder')->plainTextToken;
    return config('cms.editor_base_url') . "/editor?mode=layout&type={$type}&token={$token}";
}
```

Blade templates open these URLs with `target="_blank"`. No callback or iframe needed — Puck saves directly to the API.

---

## 4. Admin UI (Livewire + Flux)

### Layout
New `resources/views/layouts/admin.blade.php`. Sidebar navigation:
- **Content:** Posts, Pages
- **Taxonomy:** Categories, Tags
- **Library:** Media Library
- **System:** Settings

Top bar shows authenticated user name and logout. Unauthenticated requests redirect to `/login` (Fortify default).

### Middleware
New `app/Http/Middleware/AdminMiddleware.php` — checks `auth()->user()->is_admin`. Returns 403 if false.

### Livewire Components

| Class | View | Route | Key features |
|---|---|---|---|
| `Admin\Posts\Index` | `livewire/admin/posts/index` | `/admin/posts` | Search, status filter, Flux table; "Edit with Puck" opens `/post-builder` in new tab; create/edit modal for metadata (title, slug, excerpt, category, tags, SEO, status) only |
| `Admin\Pages\Index` | `livewire/admin/pages/index` | `/admin/pages` | Drag-to-reorder (SortableJS via Alpine), "Edit with Puck" opens `/builder` in new tab; create/edit modal for metadata only; revision history drawer |
| `Admin\Categories\Index` | `livewire/admin/categories/index` | `/admin/categories` | Inline create, inline edit, delete confirm |
| `Admin\Tags\Index` | `livewire/admin/tags/index` | `/admin/tags` | Same as categories |
| `Admin\MediaLibrary\Index` | `livewire/admin/media-library/index` | `/admin/media-library` | Thumbnail grid, upload dropzone, copy URL to clipboard, delete |
| `Admin\MediaLibrary\PickerModal` | `livewire/admin/media-library/picker-modal` | (modal) | Reusable picker modal — triggered from Posts, Pages, Settings, or any admin component that needs a media selection; emits `mediaSelected` event with the URL |
| `Admin\Settings\Index` | `livewire/admin/settings/index` | `/admin/settings` | Grouped key-value form; Layout tab has "Edit Header" / "Edit Footer" buttons opening Puck `/editor` in new tab; Media Library tab shows the full `MediaLibrary\Index` inline |

### UI Standards
- All components use Flux primitives exclusively: `flux:table`, `flux:modal`, `flux:input`, `flux:select`, `flux:textarea`, `flux:button`, `flux:badge`, `flux:card`
- Status badges: amber = draft, green = published
- SortableJS must be added to agrosal's `package.json` (not yet present); used only for page drag-to-reorder
- No other third-party UI libraries

---

## 5. API Endpoints

### Public endpoints — no auth, under `/api/v1/`

| Method | Endpoint | Notes |
|---|---|---|
| GET | `/api/v1/posts` | Paginated published posts. Query: `?category`, `?tag`, `?search`, `?per_page` |
| GET | `/api/v1/posts/{slug}` | Single published post with full Puck JSON content |
| GET | `/api/v1/pages` | Published pages list (title, slug, template, sort_order) |
| GET | `/api/v1/pages/{slug}` | Single published page with full Puck JSON content |
| GET | `/api/v1/settings/public` | Flat key-value of all settings where `is_public = true` |
| GET | `/api/v1/layout` | Returns `header_content` and `footer_content` as parsed JSON |

### Admin endpoints — Sanctum `auth:sanctum` middleware, under `/api/v1/admin/`

| Method | Endpoint | Used by |
|---|---|---|
| GET | `/api/v1/admin/pages` | Puck: list all pages |
| GET | `/api/v1/admin/pages/{page}` | Puck: load existing page for editing |
| POST | `/api/v1/admin/pages` | Puck: create new page |
| PUT | `/api/v1/admin/pages/{page}` | Puck: save page edits |
| GET | `/api/v1/admin/posts` | Puck: list all posts |
| GET | `/api/v1/admin/posts/{post}` | Puck: load existing post for editing |
| POST | `/api/v1/admin/posts` | Puck: create new post |
| PUT | `/api/v1/admin/posts/{post}` | Puck: save post edits |
| PUT | `/api/v1/layout/header` | Puck: save header content |
| PUT | `/api/v1/layout/footer` | Puck: save footer content |
| POST | `/api/v1/media/upload` | Puck: upload image from editor |
| GET | `/api/v1/media` | Puck: browse media library |

### Response envelope
```json
{ "data": { ... }, "meta": { "current_page": 1, "last_page": 5, "total": 48 } }
```
404s return `{ "message": "Not found" }`.

### Controllers
- `app/Http/Controllers/Api/V1/PostController.php` — public
- `app/Http/Controllers/Api/V1/PageController.php` — public
- `app/Http/Controllers/Api/V1/SettingsController.php` — public
- `app/Http/Controllers/Api/V1/LayoutController.php` — public GET, auth PUT
- `app/Http/Controllers/Api/V1/Admin/PostController.php` — auth CRUD
- `app/Http/Controllers/Api/V1/Admin/PageController.php` — auth CRUD
- `app/Http/Controllers/Api/V1/MediaController.php` — auth upload + list

---

## 6. Routing

### `routes/admin.php` (new file, registered in `bootstrap/app.php`)
```
GET /admin              → redirect → /admin/posts
GET /admin/posts        → Admin\Posts\Index
GET /admin/pages        → Admin\Pages\Index
GET /admin/categories   → Admin\Categories\Index
GET /admin/tags         → Admin\Tags\Index
GET /admin/media-library → Admin\MediaLibrary\Index
GET /admin/settings     → Admin\Settings\Index
```
Middleware: `['web', 'auth', 'admin']`

### `routes/api.php` additions
```
# Public
GET  /api/v1/posts
GET  /api/v1/posts/{slug}
GET  /api/v1/pages
GET  /api/v1/pages/{slug}
GET  /api/v1/settings/public
GET  /api/v1/layout

# Auth (Sanctum)
GET  /api/v1/admin/pages
GET  /api/v1/admin/pages/{page}
POST /api/v1/admin/pages
PUT  /api/v1/admin/pages/{page}
GET  /api/v1/admin/posts
GET  /api/v1/admin/posts/{post}
POST /api/v1/admin/posts
PUT  /api/v1/admin/posts/{post}
PUT  /api/v1/layout/header
PUT  /api/v1/layout/footer
POST /api/v1/media/upload
GET  /api/v1/media
```

---

## 7. File Delivery Checklist

| # | File | Action |
|---|---|---|
| 1 | `composer.json` | Modify — add `laravel/sanctum` |
| 2 | `config/cms.php` | New |
| 3 | `database/migrations/*_add_is_admin_to_users_table.php` | New |
| 4 | `database/migrations/*_create_personal_access_tokens_table.php` | New (Sanctum) |
| 5 | `database/migrations/*_create_categories_table.php` | New |
| 6 | `database/migrations/*_create_tags_table.php` | New |
| 7 | `database/migrations/*_create_posts_table.php` | New |
| 8 | `database/migrations/*_create_post_tag_table.php` | New |
| 9 | `database/migrations/*_create_pages_table.php` | New |
| 10 | `database/migrations/*_create_page_revisions_table.php` | New |
| 11 | `database/migrations/*_create_media_library_table.php` | New |
| 12 | `database/migrations/*_create_settings_table.php` | New |
| 13 | `database/seeders/SettingsSeeder.php` | New — seeds header_content + footer_content |
| 14 | `app/Models/User.php` | Modify — add `HasApiTokens` |
| 15 | `app/Models/Category.php` | New |
| 16 | `app/Models/Tag.php` | New |
| 17 | `app/Models/Post.php` | New |
| 18 | `app/Models/Page.php` | New |
| 19 | `app/Models/PageRevision.php` | New |
| 20 | `app/Models/MediaLibrary.php` | New |
| 21 | `app/Models/Setting.php` | New |
| 22 | `app/Http/Middleware/AdminMiddleware.php` | New |
| 23 | `routes/admin.php` | New |
| 24 | `bootstrap/app.php` | Modify — register admin.php + AdminMiddleware |
| 25 | `routes/api.php` | Modify — add all v1 CMS routes |
| 26 | `resources/views/layouts/admin.blade.php` | New |
| 27 | `app/Livewire/Admin/Posts/Index.php` | New |
| 28 | `resources/views/livewire/admin/posts/index.blade.php` | New |
| 29 | `app/Livewire/Admin/Pages/Index.php` | New |
| 30 | `resources/views/livewire/admin/pages/index.blade.php` | New |
| 31 | `app/Livewire/Admin/Categories/Index.php` | New |
| 32 | `resources/views/livewire/admin/categories/index.blade.php` | New |
| 33 | `app/Livewire/Admin/Tags/Index.php` | New |
| 34 | `resources/views/livewire/admin/tags/index.blade.php` | New |
| 35 | `app/Livewire/Admin/MediaLibrary/Index.php` | New |
| 36 | `resources/views/livewire/admin/media-library/index.blade.php` | New |
| 36a | `app/Livewire/Admin/MediaLibrary/PickerModal.php` | New |
| 36b | `resources/views/livewire/admin/media-library/picker-modal.blade.php` | New |
| 37 | `app/Livewire/Admin/Settings/Index.php` | New |
| 38 | `resources/views/livewire/admin/settings/index.blade.php` | New |
| 39 | `app/Http/Controllers/Api/V1/PostController.php` | New — public |
| 40 | `app/Http/Controllers/Api/V1/PageController.php` | New — public |
| 41 | `app/Http/Controllers/Api/V1/SettingsController.php` | New — public |
| 42 | `app/Http/Controllers/Api/V1/LayoutController.php` | New — public GET, auth PUT |
| 43 | `app/Http/Controllers/Api/V1/Admin/PostController.php` | New — auth CRUD |
| 44 | `app/Http/Controllers/Api/V1/Admin/PageController.php` | New — auth CRUD |
| 45 | `app/Http/Controllers/Api/V1/MediaController.php` | New — auth upload + list |
| 46 | `package.json` | Modify — add sortablejs |
