# Puck Editor Integration + API-Driven Public Pages

**Date:** 2026-05-31
**Approach:** Approach A — single `/builder` route for all three content types (pages, products, posts)

---

## Overview

Three deliverables:

1. **Puck editor flow** — Admin panel buttons open a Next.js `/builder` page (new tab) pre-loaded with a short-lived Sanctum token. The builder reads and writes `puck_data` via the admin API.
2. **Backend schema + API changes** — Add `puck_data` to posts; add admin `store` endpoints for pages and posts; update public post API to expose `puck_data`.
3. **API-driven public pages** — Products listing, product detail, posts listing, and post detail pages in Next.js all fetch live data from the Laravel public API instead of hardcoded/local-file data.

---

## Part 1 — Laravel Backend

### 1.1 Migration: `puck_data` on posts

**File:** `database/migrations/xxxx_add_puck_data_to_posts_table.php`

```php
Schema::table('posts', function (Blueprint $table) {
    $table->json('puck_data')->nullable()->after('content');
});
```

### 1.2 Post model

**File:** `app/Models/Post.php`

- Add `puck_data` to `$fillable`
- Add `'puck_data' => 'array'` to `$casts`

### 1.3 Admin Post API

**File:** `app/Http/Controllers/Api/V1/Admin/PostController.php`

**`show()`** — add `puck_data` to response:
```php
'puck_data' => $post->puck_data,
```

**`update()`** — add validation rule:
```php
'puck_data' => 'sometimes|nullable|array',
```

**New `store()` method** — create a draft post from the builder (`id=new` flow):

```php
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'title'     => 'required|array',
        'title.en'  => 'required|string|max:255',
        'title.bn'  => 'nullable|string|max:255',
        'slug'      => 'nullable|string|unique:posts,slug',
        'status'    => 'sometimes|in:draft,published',
        'puck_data' => 'nullable|array',
    ]);

    $validated['user_id'] = $request->user()->id;
    $validated['status'] = $validated['status'] ?? 'draft';

    $post = Post::create($validated);

    return response()->json(['data' => ['id' => $post->id, 'slug' => $post->slug]], 201);
}
```

**New route:** `POST /api/v1/admin/posts`

### 1.4 Admin Page API

**File:** `app/Http/Controllers/Api/V1/Admin/PageController.php`

**New `store()` method** — create a draft page from the builder (`id=new` flow):

```php
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'title'     => 'required|array',
        'title.en'  => 'required|string|max:255',
        'title.bn'  => 'nullable|string|max:255',
        'slug'      => 'nullable|string|unique:pages,slug',
        'status'    => 'sometimes|in:draft,published',
        'template'  => 'sometimes|string|max:100',
        'puck_data' => 'nullable|array',
    ]);

    $validated['user_id'] = $request->user()->id;
    $validated['status']  = $validated['status'] ?? 'draft';
    $validated['template'] = $validated['template'] ?? 'puck';

    $page = Page::create($validated);

    return response()->json(['data' => ['id' => $page->id, 'slug' => $page->slug]], 201);
}
```

**New route:** `POST /api/v1/admin/pages`

### 1.5 Public Post API

**File:** `app/Http/Controllers/Api/V1/PostController.php`

In `show()`, add `puck_data` alongside `content`:
```php
if ($withContent) {
    $data['content']   = $post->getTranslation('content', $locale, useFallbackLocale: true);
    $data['puck_data'] = $post->puck_data;
}
```

In `formatPost()`, add the `withContent` parameter handling for `puck_data`.

### 1.6 Routes

**File:** `routes/api.php` — inside the `auth:sanctum + can:access-admin` group:

```php
Route::post('/pages', [AdminPageController::class, 'store'])->name('pages.store');
Route::post('/posts', [AdminPostController::class, 'store'])->name('posts.store');
```

### 1.7 Posts Livewire — update editor URLs

**File:** `app/Livewire/Admin/Posts/Index.php`

Change `openPuckEditor`:
```php
$url = config('cms.editor_base_url') . "/builder?type=post&id={$postId}&token={$token}";
```

Change `openPuckEditorNew`:
```php
$url = config('cms.editor_base_url') . '/builder?type=post&id=new&token=' . $token;
```

### 1.8 Admin layout — `open-puck-editor` event handler

**File:** `resources/views/layouts/admin.blade.php`

Add before `</body>`:
```html
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('open-puck-editor', ({ url }) => window.open(url, '_blank'));
    });
</script>
```

---

## Part 2 — Next.js `/builder` Route

### 2.1 Environment variable

**File:** `agrosal-frontend/.env.local`

```
NEXT_PUBLIC_LARAVEL_API_URL=http://localhost:8000
```

The existing server-side `LARAVEL_API_URL` stays for public page fetching. `NEXT_PUBLIC_LARAVEL_API_URL` is needed because the builder is a client component.

### 2.2 Builder page

**File:** `agrosal-frontend/src/app/builder/page.tsx`

- `"use client"`
- Reads `type`, `id`, `token` from `useSearchParams()`

**URL contract:**
| Param | Values | Source (admin) |
|---|---|---|
| `type` | `page` \| `product` \| `post` | Livewire `openPuckEditor` |
| `id` | numeric \| `new` | Record ID or new |
| `token` | short-lived Sanctum token | Created in Livewire, expires 5 min |

**API path mapping:**
| type | GET (load) | PUT (save) | POST (create, id=new) |
|---|---|---|---|
| `page` | `/api/v1/admin/pages/{id}` | `/api/v1/admin/pages/{id}` | `/api/v1/admin/pages` |
| `product` | `/api/v1/admin/products/{id}` | `/api/v1/admin/products/{id}` | N/A (products always have ID) |
| `post` | `/api/v1/admin/posts/{id}` | `/api/v1/admin/posts/{id}` | `/api/v1/admin/posts` |

**State machine:**

```
mounting
  → (id=new)  → show TitleForm overlay
                → user submits title
                  → POST /api/v1/admin/{type}s  → get newId
                  → replaceState URL with id=newId
                  → fall into loading state

  → (id is numeric) → loading
                        → GET .../admin/{type}s/{id}  → extract puck_data
                        → editing  → Puck editor rendered

editing
  → user triggers save (onPublish)
    → PUT .../admin/{type}s/{id}  { puck_data: data }
    → success: show "Saved ✓" toast (auto-dismiss 2s)
    → 401: show "Session expired — reopen editor from admin" error (permanent)
    → other error: show "Save failed" toast
```

**Layout:** Full viewport. No site header/footer. Puck's own chrome (sidebar + top bar) is the full UI. Wrap in a `<div className="h-screen">`.

**puckData default for new/empty records:**
```ts
const EMPTY_DATA = { root: { props: {} }, content: [] }
```

### 2.3 Puck editor component usage

```tsx
<Puck
  config={puckConfig}
  data={puckData}
  onPublish={handleSave}
/>
```

`onPublish` receives the full Puck data object; send `puck_data: data` in the PUT body.

---

## Part 3 — Next.js Public Pages

### 3.1 Products listing page

**File:** `agrosal-frontend/src/app/products/page.tsx`

Change to `async` server component. Replace `readPageData(["products"])` with `await fetchPageData(["products"])`. The `fetchPageData` function already handles API-first + JSON fallback — no other logic changes.

### 3.2 Products detail page

**File:** `agrosal-frontend/src/app/products/[slug]/page.tsx`

Replace the entire hardcoded file with a server component:

```
GET /api/v1/products/{slug}
  → extract puck_data
  → <PuckRenderer data={puck_data} />
```

If the product has no `puck_data` yet (null), render a minimal fallback showing the product `name` and `excerpt` so the page doesn't break before content is authored.

**`generateStaticParams`:** `GET /api/v1/products` → map `data[].slug`.

**`generateMetadata`:** use `seo_title` / `seo_description` from response; fallback to product `name`.

### 3.3 Posts listing page

**File:** `agrosal-frontend/src/app/posts/page.tsx` — NEW

```
GET /api/v1/posts?per_page=18
  → render card grid
     each card: featured_image, title, excerpt, category badge, published_at
     links to /posts/{slug}
```

Uses `PageLayout` wrapper and matches existing site design (Tailwind classes, AgroSAL brand colours).

### 3.4 Posts detail page

**File:** `agrosal-frontend/src/app/posts/[slug]/page.tsx` — NEW

```
GET /api/v1/posts/{slug}
  → if puck_data present: <PuckRenderer data={puck_data} />
  → else: sanitize content with sanitize-html, render as dangerouslySetInnerHTML
```

**Dependency:** Add `sanitize-html` + `@types/sanitize-html` to the frontend. It runs server-side in the server component, so DOMPurify (browser-only) is not appropriate. `sanitize-html` is the correct choice for Node/server rendering.

```ts
import sanitizeHtml from 'sanitize-html'
// ...
const safeHtml = sanitizeHtml(rawContent ?? '', {
  allowedTags: sanitizeHtml.defaults.allowedTags.concat(['img']),
  allowedAttributes: { ...sanitizeHtml.defaults.allowedAttributes, img: ['src', 'alt'] },
})
// <div dangerouslySetInnerHTML={{ __html: safeHtml }} />
```

**`generateStaticParams`:** `GET /api/v1/posts` → map `data[].slug`.

**`generateMetadata`:** `seo_title` / `seo_description`; fallback to post `title`.

---

## Data Flow Summary

```
Admin clicks "Edit in Puck" on a Page/Product/Post
  └─ Livewire openPuckEditor(id)
      └─ Creates short-lived Sanctum token (5 min)
      └─ Builds URL: /builder?type=page&id=5&token=xxx
      └─ Dispatches open-puck-editor event
          └─ JS handler: window.open(url, '_blank')
              └─ Next.js /builder?type=page&id=5&token=xxx
                  └─ GET /api/v1/admin/pages/5 (Bearer token)
                      └─ Puck editor loads with puck_data
                          └─ User edits, clicks "Publish"
                              └─ PUT /api/v1/admin/pages/5 { puck_data: {...} }
                                  └─ "Saved ✓" toast

Public visitor loads /about
  └─ Next.js [...slug]/page.tsx
      └─ fetchPageData(['about'])
          └─ GET /api/v1/pages/about → puck_data
              └─ <PuckRenderer> renders the page

Public visitor loads /products/agroprime
  └─ Next.js products/[slug]/page.tsx
      └─ GET /api/v1/products/agroprime → puck_data
          └─ <PuckRenderer> renders the product detail
```

---

## Error Handling

| Scenario | Behaviour |
|---|---|
| Token expired (401) when saving | Show permanent error banner: "Session expired — reopen editor from admin panel" |
| API unreachable when loading builder | Show "Failed to load content" + retry button |
| Product/post has no puck_data yet | Show minimal fallback (name + excerpt); still fully functional for new authoring |
| Public page/product 404 | `notFound()` → Next.js 404 page |
| Builder opened with invalid type | Show "Invalid editor type" error |

---

## File Changes Summary

| File | Change |
|---|---|
| `database/migrations/xxxx_add_puck_data_to_posts_table.php` | NEW |
| `app/Models/Post.php` | Add puck_data to fillable + cast |
| `app/Http/Controllers/Api/V1/Admin/PostController.php` | add puck_data to show/update; add store() |
| `app/Http/Controllers/Api/V1/Admin/PageController.php` | add store() |
| `app/Http/Controllers/Api/V1/PostController.php` | return puck_data in show |
| `routes/api.php` | POST /admin/pages + POST /admin/posts |
| `app/Livewire/Admin/Posts/Index.php` | Update openPuckEditor URLs |
| `resources/views/layouts/admin.blade.php` | Add JS event handler |
| `agrosal-frontend/.env.local` | Add NEXT_PUBLIC_LARAVEL_API_URL |
| `agrosal-frontend/src/app/builder/page.tsx` | NEW |
| `agrosal-frontend/src/app/products/page.tsx` | Make async + use fetchPageData |
| `agrosal-frontend/src/app/products/[slug]/page.tsx` | Replace with API + PuckRenderer |
| `agrosal-frontend/src/app/posts/page.tsx` | NEW |
| `agrosal-frontend/src/app/posts/[slug]/page.tsx` | NEW |
| `agrosal-frontend/package.json` | Add sanitize-html + @types/sanitize-html |
