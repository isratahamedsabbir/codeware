# Puck Editor Integration + API-Driven Public Pages Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Wire the Puck visual editor into the admin panel for pages, products, and posts, and serve all three content types from the Laravel API on public Next.js pages.

**Architecture:** Laravel gains `puck_data` on posts, `store()` endpoints for pages/posts, and a JS event listener in the admin layout that opens the Next.js `/builder` route in a new tab carrying a short-lived Sanctum token. The unified `/builder` client component reads `type`/`id`/`token` from URL params, loads content via the admin API, renders the Puck editor, and PUTs back on save. Public Next.js product and post pages become server components that fetch from the public Laravel API and render `puck_data` via PuckRenderer.

**Tech Stack:** Laravel 11 / Pest v4 / Livewire v3 / Sanctum / Next.js 16 / React 19 / @measured/puck 0.20.x / TypeScript / Tailwind CSS v4 / sanitize-html

**PHP binary:** `C:\Users\User\.config\herd\bin\php85\php.exe` (run from `d:\herd\agrosal`)

---

## File Map

| File | Status | Responsibility |
|---|---|---|
| `database/migrations/2026_05_31_000000_add_puck_data_to_posts_table.php` | NEW | Add `puck_data` JSON nullable column to posts |
| `app/Models/Post.php` | MODIFY | Add `puck_data` to `$fillable` and `$casts` |
| `app/Http/Controllers/Api/V1/Admin/PostController.php` | MODIFY | `puck_data` in show/update; new `store()` |
| `app/Http/Controllers/Api/V1/Admin/PageController.php` | MODIFY | New `store()` |
| `app/Http/Controllers/Api/V1/PostController.php` | MODIFY | Return `puck_data` in public `show()` |
| `routes/api.php` | MODIFY | Add `POST /api/v1/admin/pages` and `POST /api/v1/admin/posts` |
| `app/Livewire/Admin/Posts/Index.php` | MODIFY | Change `openPuckEditor` URLs to use `/builder?type=post` |
| `resources/views/layouts/admin.blade.php` | MODIFY | JS handler that opens the puck-editor URL in a new tab |
| `tests/Feature/Cms/AdminPostPuckTest.php` | NEW | Admin post API puck_data tests |
| `tests/Feature/Cms/AdminPageStoreTest.php` | NEW | Admin page store endpoint tests |
| `tests/Feature/Cms/PublicPostPuckTest.php` | NEW | Public post API puck_data tests |
| `agrosal-frontend/.env.local` | MODIFY | Add `NEXT_PUBLIC_LARAVEL_API_URL` |
| `agrosal-frontend/package.json` | MODIFY | Add `sanitize-html` + `@types/sanitize-html` |
| `agrosal-frontend/src/app/builder/page.tsx` | NEW | Unified Puck editor (pages, products, posts) |
| `agrosal-frontend/src/app/products/page.tsx` | MODIFY | Make async, use `fetchPageData` |
| `agrosal-frontend/src/app/products/[slug]/page.tsx` | REPLACE | API-driven server component with PuckRenderer |
| `agrosal-frontend/src/app/posts/page.tsx` | NEW | Posts listing from public API |
| `agrosal-frontend/src/app/posts/[slug]/page.tsx` | NEW | Post detail: PuckRenderer or sanitized HTML |

---

### Task 1: Migration — add `puck_data` to posts

**Files:**
- Create: `database/migrations/2026_05_31_000000_add_puck_data_to_posts_table.php`
- Modify: `app/Models/Post.php`

- [ ] **Step 1: Generate the migration**

```bash
php artisan make:migration add_puck_data_to_posts_table --table=posts
```

Rename the generated file to `2026_05_31_000000_add_puck_data_to_posts_table.php` if the timestamp differs, then replace its content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->json('puck_data')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('puck_data');
        });
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected output includes: `Running migrations... add_puck_data_to_posts_table ........ DONE`

- [ ] **Step 3: Update Post model**

Open `app/Models/Post.php`. Add `puck_data` to `$fillable` and add a cast:

```php
protected $fillable = [
    'user_id', 'category_id', 'title', 'slug', 'excerpt',
    'content', 'puck_data', 'featured_image', 'status', 'published_at',
    'reading_time', 'seo_title', 'seo_description',
];

protected $casts = [
    'published_at' => 'datetime',
    'puck_data'    => 'array',
];
```

- [ ] **Step 4: Verify the column exists**

```bash
php artisan tinker --execute="echo Schema::hasColumn('posts', 'puck_data') ? 'OK' : 'MISSING';"
```

Expected output: `OK`

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_05_31_000000_add_puck_data_to_posts_table.php app/Models/Post.php
git commit -m "feat: add puck_data column to posts table"
```

---

### Task 2: Admin Post API — `puck_data` in show/update

**Files:**
- Create: `tests/Feature/Cms/AdminPostPuckTest.php`
- Modify: `app/Http/Controllers/Api/V1/Admin/PostController.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/Cms/AdminPostPuckTest.php`:

```php
<?php

use App\Models\Post;
use App\Models\User;

it('admin post show includes puck_data', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $post = Post::factory()->create([
        'puck_data' => ['root' => ['props' => []], 'content' => []],
    ]);

    $this->withToken($token)
        ->getJson("/api/v1/admin/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('data.puck_data.content', []);
});

it('admin post update accepts puck_data', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;
    $post  = Post::factory()->create();

    $puck = ['root' => ['props' => []], 'content' => [['type' => 'HeroSection', 'props' => []]]];

    $this->withToken($token)
        ->putJson("/api/v1/admin/posts/{$post->id}", ['puck_data' => $puck])
        ->assertOk()
        ->assertJsonPath('data.id', $post->id);

    expect($post->fresh()->puck_data['content'][0]['type'])->toBe('HeroSection');
});
```

- [ ] **Step 2: Run tests to see them fail**

```bash
php artisan test --filter "admin post show includes puck_data"
```

Expected: `FAILED — undefined array key "puck_data"` or similar (show does not return it yet).

- [ ] **Step 3: Update Admin PostController show and update**

In `app/Http/Controllers/Api/V1/Admin/PostController.php`:

In `show()`, add `puck_data` to the response array after `seo_description`:

```php
'seo_description' => $post->seo_description,
'puck_data'       => $post->puck_data,
```

In `update()`, add the validation rule after the `featured_image` rule:

```php
'puck_data' => 'sometimes|nullable|array',
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
php artisan test --filter "AdminPostPuck"
```

Expected: `2 passed`

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Cms/AdminPostPuckTest.php app/Http/Controllers/Api/V1/Admin/PostController.php
git commit -m "feat: add puck_data to admin post API show/update"
```

---

### Task 3: Admin Post API — `store()` endpoint

**Files:**
- Modify: `tests/Feature/Cms/AdminPostPuckTest.php` (add tests)
- Modify: `app/Http/Controllers/Api/V1/Admin/PostController.php` (add store)
- Modify: `routes/api.php`

- [ ] **Step 1: Add failing test**

Append to `tests/Feature/Cms/AdminPostPuckTest.php`:

```php
it('admin can create a post via store', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/admin/posts', [
            'title' => ['en' => 'Builder Post', 'bn' => ''],
        ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'slug']]);

    expect(\App\Models\Post::where('status', 'draft')->exists())->toBeTrue();
});

it('admin post store requires en title', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/admin/posts', ['title' => ['bn' => 'শিরোনাম']])
        ->assertUnprocessable();
});
```

- [ ] **Step 2: Run tests to see them fail**

```bash
php artisan test --filter "admin can create a post via store"
```

Expected: `FAILED — 405 Method Not Allowed` (route does not exist yet).

- [ ] **Step 3: Add store() to Admin PostController**

Add this method to `app/Http/Controllers/Api/V1/Admin/PostController.php` before `update()`:

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
    $validated['status']  = $validated['status'] ?? 'draft';

    $post = Post::create($validated);

    return response()->json(['data' => ['id' => $post->id, 'slug' => $post->slug]], 201);
}
```

Add the `Post` import at the top if it is missing (it's already there).

- [ ] **Step 4: Add POST route for admin posts**

In `routes/api.php`, inside the `auth:sanctum + can:access-admin` group, after the existing posts routes:

```php
Route::post('/posts', [AdminPostController::class, 'store'])->name('posts.store');
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --filter "AdminPostPuck"
```

Expected: `4 passed`

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Cms/AdminPostPuckTest.php app/Http/Controllers/Api/V1/Admin/PostController.php routes/api.php
git commit -m "feat: add admin post store endpoint for puck builder"
```

---

### Task 4: Admin Page API — `store()` endpoint

**Files:**
- Create: `tests/Feature/Cms/AdminPageStoreTest.php`
- Modify: `app/Http/Controllers/Api/V1/Admin/PageController.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/Cms/AdminPageStoreTest.php`:

```php
<?php

use App\Models\User;

it('admin can create a page via store', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/admin/pages', [
            'title' => ['en' => 'New Builder Page', 'bn' => ''],
        ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'slug']]);

    expect(\App\Models\Page::where('template', 'puck')->where('status', 'draft')->exists())->toBeTrue();
});

it('admin page store requires en title', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $token = $admin->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/admin/pages', ['title' => ['bn' => 'শিরোনাম']])
        ->assertUnprocessable();
});
```

- [ ] **Step 2: Run tests to see them fail**

```bash
php artisan test --filter "admin can create a page via store"
```

Expected: `FAILED — 405 Method Not Allowed`

- [ ] **Step 3: Add store() to Admin PageController**

Add this method to `app/Http/Controllers/Api/V1/Admin/PageController.php` before `update()`. Add `use App\Models\Page;` at the top if missing:

```php
public function store(Request $request): JsonResponse
{
    $validated = $request->validate([
        'title'     => 'required|array',
        'title.en'  => 'required|string|max:255',
        'title.bn'  => 'nullable|string|max:255',
        'slug'      => 'nullable|string|unique:pages,slug',
        'status'    => 'sometimes|in:draft,published',
        'template'  => 'sometimes|nullable|string|max:100',
        'puck_data' => 'nullable|array',
    ]);

    $validated['user_id']  = $request->user()->id;
    $validated['status']   = $validated['status'] ?? 'draft';
    $validated['template'] = $validated['template'] ?? 'puck';

    $page = Page::create($validated);

    return response()->json(['data' => ['id' => $page->id, 'slug' => $page->slug]], 201);
}
```

- [ ] **Step 4: Add POST route for admin pages**

In `routes/api.php`, inside the admin group, after existing pages routes:

```php
Route::post('/pages', [AdminPageController::class, 'store'])->name('pages.store');
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --filter "AdminPageStore"
```

Expected: `2 passed`

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Cms/AdminPageStoreTest.php app/Http/Controllers/Api/V1/Admin/PageController.php routes/api.php
git commit -m "feat: add admin page store endpoint for puck builder"
```

---

### Task 5: Public Post API — return `puck_data` in show

**Files:**
- Create: `tests/Feature/Cms/PublicPostPuckTest.php`
- Modify: `app/Http/Controllers/Api/V1/PostController.php`

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Cms/PublicPostPuckTest.php`:

```php
<?php

use App\Models\Post;

it('public post show includes puck_data when present', function () {
    $post = Post::factory()->published()->create([
        'puck_data' => [
            'root'    => ['props' => []],
            'content' => [['type' => 'HeroSection', 'props' => []]],
        ],
    ]);

    $this->getJson("/api/v1/posts/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.puck_data.content.0.type', 'HeroSection');
});

it('public post show returns null puck_data when not set', function () {
    $post = Post::factory()->published()->create(['puck_data' => null]);

    $this->getJson("/api/v1/posts/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.puck_data', null);
});
```

- [ ] **Step 2: Run tests to see them fail**

```bash
php artisan test --filter "PublicPostPuck"
```

Expected: `FAILED — Failed asserting that null is equal to 'HeroSection'`

- [ ] **Step 3: Update public PostController**

In `app/Http/Controllers/Api/V1/PostController.php`, update `formatPost()`. Find the `if ($withContent)` block and add `puck_data`:

```php
if ($withContent) {
    $data['content']   = $post->getTranslation('content', $locale, useFallbackLocale: true);
    $data['puck_data'] = $post->puck_data;
}
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --filter "PublicPostPuck"
```

Expected: `2 passed`

- [ ] **Step 5: Run the full test suite to check for regressions**

```bash
php artisan test
```

Expected: all tests pass.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/Cms/PublicPostPuckTest.php app/Http/Controllers/Api/V1/PostController.php
git commit -m "feat: expose puck_data in public post API show response"
```

---

### Task 6: Update Posts Livewire — editor URLs

**Files:**
- Modify: `app/Livewire/Admin/Posts/Index.php`

- [ ] **Step 1: Update openPuckEditor URLs**

In `app/Livewire/Admin/Posts/Index.php`, update both methods:

Replace `openPuckEditor`:

```php
public function openPuckEditor(int $postId): void
{
    auth()->user()->tokens()->where('name', 'puck-builder')->delete();

    $token = auth()->user()->createToken(
        'puck-builder',
        ['*'],
        now()->addMinutes(config('app.puck_session', 5))
    )->plainTextToken;

    $url = config('cms.editor_base_url') . "/builder?type=post&id={$postId}&token={$token}";
    $this->dispatch('open-puck-editor', url: $url);
}
```

Replace `openPuckEditorNew`:

```php
public function openPuckEditorNew(): void
{
    auth()->user()->tokens()->where('name', 'puck-builder')->delete();

    $token = auth()->user()->createToken(
        'puck-builder',
        ['*'],
        now()->addMinutes(config('app.puck_session', 5))
    )->plainTextToken;

    $url = config('cms.editor_base_url') . '/builder?type=post&id=new&token=' . $token;
    $this->dispatch('open-puck-editor', url: $url);
}
```

- [ ] **Step 2: Verify the Pages and Products Livewire already use the right URL shape**

Check `app/Livewire/Admin/Pages/Index.php` — it should dispatch:
`config('cms.editor_base_url') . "/builder?page={$pageId}&token={$token}"`

Check `app/Livewire/Admin/Products/Index.php` — it should dispatch:
`config('cms.editor_base_url') . "/builder?type=product&id={$id}&token={$token}"`

These existing URLs already point to `/builder` — no change needed on Pages or Products.

**Note:** The builder (Task 8) will read `?page=X` for pages, `?type=product&id=X` for products, and `?type=post&id=X` for posts.

- [ ] **Step 3: Commit**

```bash
git add app/Livewire/Admin/Posts/Index.php
git commit -m "feat: update post puck editor URL to use unified /builder route"
```

---

### Task 7: Admin layout — `open-puck-editor` event handler

**Files:**
- Modify: `resources/views/layouts/admin.blade.php`

- [ ] **Step 1: Add JS listener before `</body>`**

In `resources/views/layouts/admin.blade.php`, find `@fluxScripts` near the bottom. Add the script block immediately before it:

```html
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-puck-editor', ({ url }) => window.open(url, '_blank'));
        });
    </script>
    @fluxScripts
```

- [ ] **Step 2: Manual smoke test**

Start the Laravel dev server (`composer dev`). Log in to the admin panel. Go to Pages. Click the "Edit in Puck" button on any page. Verify a new browser tab opens pointing to `http://localhost:3000/builder?page=...&token=...`. (The Next.js app doesn't exist yet — a 404 at this point is expected. You are verifying the tab opens and the URL shape is correct.)

- [ ] **Step 3: Commit**

```bash
git add resources/views/layouts/admin.blade.php
git commit -m "feat: open puck editor in new tab from admin panel"
```

---

### Task 8: Next.js — install sanitize-html and set env

**Files:**
- Modify: `agrosal-frontend/package.json` (via npm install)
- Modify: `agrosal-frontend/.env.local`

All commands run from `agrosal-frontend/`.

- [ ] **Step 1: Install sanitize-html**

```bash
cd agrosal-frontend
npm install sanitize-html @types/sanitize-html
```

Expected: packages added, `package-lock.json` updated.

- [ ] **Step 2: Add env variable**

Open `agrosal-frontend/.env.local`. Add:

```
NEXT_PUBLIC_LARAVEL_API_URL=http://localhost:8000
```

Keep the existing `LARAVEL_API_URL=http://localhost:8000` line (server-side use).

If `.env.local` does not exist yet, create it with both lines:

```
LARAVEL_API_URL=http://localhost:8000
NEXT_PUBLIC_LARAVEL_API_URL=http://localhost:8000
```

- [ ] **Step 3: Commit**

```bash
git add agrosal-frontend/package.json agrosal-frontend/package-lock.json agrosal-frontend/.env.local
git commit -m "chore: install sanitize-html, add NEXT_PUBLIC_LARAVEL_API_URL"
```

---

### Task 9: Next.js — `/builder` Puck editor route

**Files:**
- Create: `agrosal-frontend/src/app/builder/page.tsx`

- [ ] **Step 1: Create the builder directory and page file**

Create `agrosal-frontend/src/app/builder/page.tsx` with the full content below.

`useSearchParams()` requires a `Suspense` boundary in Next.js 13+. The file is split into an inner component (reads params) and an outer default export (provides Suspense).

The builder reads three URL params:
- `page` — page ID (for pages, e.g. `?page=3`)  ← legacy shape from Pages Livewire
- `type` — `page | product | post` (for products/posts, e.g. `?type=post&id=5`)
- `id` — numeric ID or `new`
- `token` — Sanctum bearer token

The inner component normalises these into a unified `{ type, id, token }`.

```tsx
'use client'

import { Suspense, useEffect, useState } from 'react'
import { useSearchParams } from 'next/navigation'
import { Puck, type Data } from '@measured/puck'
import '@measured/puck/puck.css'
import { puckConfig } from '@/lib/puck-config'

const EMPTY_DATA: Data = { root: { props: {} }, content: [] }

type EditorType = 'page' | 'product' | 'post'

function resourcePath(type: EditorType, id: string): string {
  const plural: Record<EditorType, string> = { page: 'pages', product: 'products', post: 'posts' }
  return `/api/v1/admin/${plural[type]}/${id}`
}

function storePath(type: 'page' | 'post'): string {
  return type === 'page' ? '/api/v1/admin/pages' : '/api/v1/admin/posts'
}

function BuilderInner() {
  const params = useSearchParams()

  // Normalise params: Pages uses ?page=X, Products/Posts use ?type=X&id=X
  const rawPage  = params.get('page')
  const rawType  = params.get('type') as EditorType | null
  const rawId    = params.get('id')
  const token    = params.get('token')

  const type: EditorType | null = rawPage ? 'page' : rawType
  const initialId = rawPage ?? rawId

  const apiBase = process.env.NEXT_PUBLIC_LARAVEL_API_URL

  const [recordId, setRecordId]   = useState<string | null>(initialId)
  const [puckData, setPuckData]   = useState<Data | null>(null)
  const [newTitle, setNewTitle]   = useState('')
  const [stage, setStage]         = useState<'loading' | 'new' | 'editing' | 'error'>('loading')
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'saved' | 'expired' | 'error'>('idle')

  useEffect(() => {
    if (!type || !token || !apiBase) { setStage('error'); return }
    if (initialId === 'new') { setStage('new'); return }

    fetch(`${apiBase}${resourcePath(type, initialId!)}`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
      .then(r => { if (!r.ok) throw new Error(String(r.status)); return r.json() })
      .then(json => {
        setPuckData((json.data?.puck_data as Data | null) ?? EMPTY_DATA)
        setStage('editing')
      })
      .catch(() => setStage('error'))
  }, []) // eslint-disable-line react-hooks/exhaustive-deps

  const handleCreate = async () => {
    if (!type || type === 'product') return
    const res = await fetch(`${apiBase}${storePath(type)}`, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({ title: { en: newTitle.trim(), bn: '' }, status: 'draft' }),
    })
    if (!res.ok) { setStage('error'); return }
    const json = await res.json()
    const newId = String(json.data.id)
    setRecordId(newId)
    window.history.replaceState({}, '', `?type=${type}&id=${newId}&token=${token}`)
    setPuckData(EMPTY_DATA)
    setStage('editing')
  }

  const handleSave = async (data: Data) => {
    if (!recordId || !type) return
    setSaveStatus('saving')
    const res = await fetch(`${apiBase}${resourcePath(type, recordId)}`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({ puck_data: data }),
    })
    if (res.status === 401) { setSaveStatus('expired'); return }
    if (!res.ok) { setSaveStatus('error'); return }
    setSaveStatus('saved')
    setTimeout(() => setSaveStatus('idle'), 2000)
  }

  if (stage === 'error' || !type || !token) {
    return (
      <div className="flex h-screen items-center justify-center bg-gray-50 text-red-600 text-sm px-8 text-center">
        Failed to load the editor. Please close this tab and reopen from the admin panel.
      </div>
    )
  }

  if (stage === 'new') {
    return (
      <div className="flex h-screen items-center justify-center bg-gray-50">
        <div className="bg-white p-8 rounded-xl shadow-lg max-w-sm w-full space-y-4">
          <h1 className="text-lg font-semibold text-gray-900">
            New {type === 'page' ? 'Page' : 'Post'}
          </h1>
          <input
            className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Title (English)"
            value={newTitle}
            onChange={e => setNewTitle(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && newTitle.trim() && handleCreate()}
            autoFocus
          />
          <button
            className="w-full bg-blue-600 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-blue-700 disabled:opacity-40 transition-colors"
            disabled={!newTitle.trim()}
            onClick={handleCreate}
          >
            Create &amp; Open Editor
          </button>
        </div>
      </div>
    )
  }

  if (stage === 'loading' || !puckData) {
    return (
      <div className="flex h-screen items-center justify-center text-gray-500 text-sm">
        Loading editor…
      </div>
    )
  }

  return (
    <div className="h-screen">
      {saveStatus === 'saved' && (
        <div className="fixed top-4 right-4 z-50 bg-green-600 text-white px-4 py-2 rounded-lg shadow text-sm pointer-events-none">
          Saved ✓
        </div>
      )}
      {saveStatus === 'expired' && (
        <div className="fixed top-4 right-4 z-50 bg-red-600 text-white px-4 py-2 rounded-lg shadow text-sm max-w-xs">
          Session expired — reopen editor from the admin panel
        </div>
      )}
      {saveStatus === 'error' && (
        <div className="fixed top-4 right-4 z-50 bg-orange-600 text-white px-4 py-2 rounded-lg shadow text-sm">
          Save failed — please try again
        </div>
      )}
      <Puck config={puckConfig} data={puckData} onPublish={handleSave} />
    </div>
  )
}

export default function BuilderPage() {
  return (
    <Suspense
      fallback={
        <div className="flex h-screen items-center justify-center text-gray-500 text-sm">
          Loading…
        </div>
      }
    >
      <BuilderInner />
    </Suspense>
  )
}
```

- [ ] **Step 2: Start the Next.js dev server**

```bash
cd agrosal-frontend
npm run dev
```

- [ ] **Step 3: Smoke-test loading an existing page**

From the admin panel, go to **Pages** and click "Edit in Puck" on any page that has `puck_data` set (e.g., a seeded home page). The browser should open a new tab at `http://localhost:3000/builder?page=1&token=...` and display the Puck editor with the existing content loaded.

If you see "Failed to load the editor", check:
1. The `NEXT_PUBLIC_LARAVEL_API_URL` env var is set in `.env.local`
2. The Laravel server is running on port 8000
3. CORS: if the browser console shows a CORS error, open `config/cors.php` and ensure `'allowed_origins'` includes `'http://localhost:3000'`

- [ ] **Step 4: Smoke-test saving**

Make a small edit in Puck (move a block, change a text field). Click the **Publish** button in Puck's toolbar. Verify:
- A green "Saved ✓" toast appears briefly
- The admin panel list still shows the page (soft verification the PUT succeeded)
- Reload the public frontend page — it should reflect the change (after Next.js revalidation at 60s, or restart the server)

- [ ] **Step 5: Smoke-test loading a product**

From the admin panel, go to **Products** and click "Edit in Puck" on any product. Verify the builder opens at `?type=product&id=X&token=...` with empty (or existing) puck data.

- [ ] **Step 6: Smoke-test creating a new post**

From the admin panel, go to **Posts** and click "New in Puck". Verify:
- The builder opens at `?type=post&id=new&token=...`
- A title input form appears
- Entering a title and pressing Enter creates the post and transitions to the editor

- [ ] **Step 7: Commit**

```bash
git add agrosal-frontend/src/app/builder/page.tsx
git commit -m "feat: add unified /builder puck editor for pages, products, and posts"
```

---

### Task 10: Next.js — products listing page (async)

**Files:**
- Modify: `agrosal-frontend/src/app/products/page.tsx`

- [ ] **Step 1: Update to async server component**

Replace the entire content of `agrosal-frontend/src/app/products/page.tsx`:

```tsx
import PageLayout from "@/components/layout/page-layout"
import { PuckRenderer } from "@/components/puck/PuckRenderer"
import { fetchPageData } from "@/lib/puck-data"
import { PuckData } from "@/types"

export default async function ProductsPage() {
  const page = await fetchPageData(["products"])

  if (!page) return null

  return (
    <PageLayout>
      <PuckRenderer data={page.puckData as unknown as PuckData} />
    </PageLayout>
  )
}
```

- [ ] **Step 2: Verify**

With the dev server running, open `http://localhost:3000/products`. The products page should render identically to before (now pulling from the API if `LARAVEL_API_URL` is set, falling back to `src/data/pages/products.json`).

- [ ] **Step 3: Commit**

```bash
git add agrosal-frontend/src/app/products/page.tsx
git commit -m "feat: make products listing page async with API-first data"
```

---

### Task 11: Next.js — product detail page (API + PuckRenderer)

**Files:**
- Replace: `agrosal-frontend/src/app/products/[slug]/page.tsx`

- [ ] **Step 1: Replace the file**

Replace the entire content of `agrosal-frontend/src/app/products/[slug]/page.tsx`:

```tsx
import PageLayout from "@/components/layout/page-layout"
import { PuckRenderer } from "@/components/puck/PuckRenderer"
import { notFound } from "next/navigation"
import { PuckData } from "@/types"

interface ProductDetailProps {
  params: Promise<{ slug: string }>
}

async function fetchProduct(slug: string) {
  const apiBase = process.env.LARAVEL_API_URL
  if (!apiBase) return null
  try {
    const res = await fetch(`${apiBase}/api/v1/products/${slug}?locale=en`, {
      next: { revalidate: 60 },
    })
    if (!res.ok) return null
    const json = await res.json()
    return (json.data as Record<string, unknown>) ?? null
  } catch {
    return null
  }
}

export default async function ProductDetailPage({ params }: ProductDetailProps) {
  const { slug } = await params
  const product = await fetchProduct(slug)

  if (!product) notFound()

  if (product.puck_data) {
    return (
      <PageLayout>
        <PuckRenderer data={product.puck_data as unknown as PuckData} />
      </PageLayout>
    )
  }

  // Fallback for products with no puck_data yet
  const name    = typeof product.name === 'string' ? product.name : (product.name as Record<string, string>)?.en ?? ''
  const excerpt = typeof product.excerpt === 'string' ? product.excerpt : (product.excerpt as Record<string, string>)?.en ?? ''

  return (
    <PageLayout>
      <div className="max-w-4xl mx-auto px-5 py-24">
        <h1 className="text-3xl font-bold text-[#111a0e] mb-4">{name}</h1>
        {excerpt && <p className="text-[#4a5e3a] text-lg leading-relaxed">{excerpt}</p>}
      </div>
    </PageLayout>
  )
}

export async function generateStaticParams() {
  const apiBase = process.env.LARAVEL_API_URL
  if (!apiBase) return []
  try {
    const res = await fetch(`${apiBase}/api/v1/products?per_page=100`, {
      next: { revalidate: 3600 },
    })
    if (!res.ok) return []
    const json = await res.json()
    return (json.data as Array<{ slug: string }>).map(p => ({ slug: p.slug }))
  } catch {
    return []
  }
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params
  const product = await fetchProduct(slug)

  if (!product) return { title: 'Product Not Found - AgroSAL' }

  const seoTitle = typeof product.seo_title === 'string'
    ? product.seo_title
    : (product.seo_title as Record<string, string>)?.en ?? ''
  const seoDesc  = typeof product.seo_description === 'string'
    ? product.seo_description
    : (product.seo_description as Record<string, string>)?.en ?? ''
  const name     = typeof product.name === 'string'
    ? product.name
    : (product.name as Record<string, string>)?.en ?? 'Product'

  return {
    title: seoTitle || `${name} - AgroSAL`,
    description: seoDesc || ((product.excerpt as Record<string, string>)?.en ?? ''),
  }
}
```

- [ ] **Step 2: Verify**

Open any seeded product URL, e.g. `http://localhost:3000/products/some-slug`.
- If `puck_data` is set on that product: the Puck-rendered layout appears.
- If `puck_data` is null: the minimal fallback (name + excerpt) appears.
- A 404 returns the Next.js not-found page.

- [ ] **Step 3: Commit**

```bash
git add agrosal-frontend/src/app/products/[slug]/page.tsx
git commit -m "feat: product detail page — API-driven PuckRenderer"
```

---

### Task 12: Next.js — posts listing page

**Files:**
- Create: `agrosal-frontend/src/app/posts/page.tsx`

- [ ] **Step 1: Create the file**

Create `agrosal-frontend/src/app/posts/page.tsx`:

```tsx
import PageLayout from "@/components/layout/page-layout"
import Link from "next/link"

interface Post {
  id: number
  slug: string
  title: string
  excerpt: string | null
  featured_image: string | null
  reading_time: number | null
  published_at: string | null
  category: { slug: string; name: string } | null
}

async function fetchPosts(): Promise<Post[]> {
  const apiBase = process.env.LARAVEL_API_URL
  if (!apiBase) return []
  try {
    const res = await fetch(`${apiBase}/api/v1/posts?per_page=18&locale=en`, {
      next: { revalidate: 60 },
    })
    if (!res.ok) return []
    const json = await res.json()
    return (json.data as Post[]) ?? []
  } catch {
    return []
  }
}

export default async function PostsPage() {
  const posts = await fetchPosts()

  return (
    <PageLayout>
      <div className="max-w-7xl mx-auto px-5 sm:px-8 py-24">
        <h1 className="text-4xl font-black text-[#111a0e] mb-3">News &amp; Insights</h1>
        <p className="text-[#4a5e3a] text-lg mb-12">Latest updates from AgroSAL</p>

        {posts.length === 0 ? (
          <p className="text-[#6b7c5e]">No posts published yet.</p>
        ) : (
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-8">
            {posts.map(post => (
              <Link
                key={post.id}
                href={`/posts/${post.slug}`}
                className="group flex flex-col rounded-2xl overflow-hidden bg-white shadow-sm ring-1 ring-[#1271B7]/10 hover:shadow-md transition-all duration-300"
              >
                {post.featured_image && (
                  <div className="aspect-video overflow-hidden bg-gray-100">
                    <img
                      src={post.featured_image}
                      alt={post.title}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    />
                  </div>
                )}
                <div className="flex flex-col flex-1 p-6 gap-2">
                  {post.category && (
                    <span className="text-xs font-bold uppercase tracking-wider text-[#1271B7]">
                      {post.category.name}
                    </span>
                  )}
                  <h2 className="text-lg font-bold text-[#111a0e] leading-snug group-hover:text-[#1271B7] transition-colors">
                    {post.title}
                  </h2>
                  {post.excerpt && (
                    <p className="text-sm text-[#4a5e3a] leading-relaxed line-clamp-3">{post.excerpt}</p>
                  )}
                  <div className="mt-auto pt-4 flex items-center gap-2 text-xs text-[#6b7c5e]">
                    {post.published_at && (
                      <span>
                        {new Date(post.published_at).toLocaleDateString('en-GB', {
                          day: 'numeric',
                          month: 'short',
                          year: 'numeric',
                        })}
                      </span>
                    )}
                    {post.reading_time && <span>· {post.reading_time} min read</span>}
                  </div>
                </div>
              </Link>
            ))}
          </div>
        )}
      </div>
    </PageLayout>
  )
}

export async function generateMetadata() {
  return {
    title: 'News & Insights - AgroSAL',
    description: 'Latest news, insights, and updates from AgroSAL.',
  }
}
```

- [ ] **Step 2: Verify**

Open `http://localhost:3000/posts`.
- If published posts exist in the DB: a card grid appears.
- If no posts: "No posts published yet." message appears.
- Cards link to `/posts/{slug}`.

- [ ] **Step 3: Commit**

```bash
git add agrosal-frontend/src/app/posts/page.tsx
git commit -m "feat: add public posts listing page from API"
```

---

### Task 13: Next.js — post detail page

**Files:**
- Create: `agrosal-frontend/src/app/posts/[slug]/page.tsx`

- [ ] **Step 1: Create the directory and file**

Create `agrosal-frontend/src/app/posts/[slug]/page.tsx`:

```tsx
import PageLayout from "@/components/layout/page-layout"
import { PuckRenderer } from "@/components/puck/PuckRenderer"
import { notFound } from "next/navigation"
import { PuckData } from "@/types"
import sanitizeHtml from "sanitize-html"

interface PostDetailProps {
  params: Promise<{ slug: string }>
}

async function fetchPost(slug: string) {
  const apiBase = process.env.LARAVEL_API_URL
  if (!apiBase) return null
  try {
    const res = await fetch(`${apiBase}/api/v1/posts/${slug}?locale=en`, {
      next: { revalidate: 60 },
    })
    if (!res.ok) return null
    const json = await res.json()
    return (json.data as Record<string, unknown>) ?? null
  } catch {
    return null
  }
}

export default async function PostDetailPage({ params }: PostDetailProps) {
  const { slug } = await params
  const post = await fetchPost(slug)

  if (!post) notFound()

  if (post.puck_data) {
    return (
      <PageLayout>
        <PuckRenderer data={post.puck_data as unknown as PuckData} />
      </PageLayout>
    )
  }

  // Fallback: render sanitized HTML content for posts without puck_data
  const rawContent = typeof post.content === 'string' ? post.content : ''
  const safeHtml = sanitizeHtml(rawContent, {
    allowedTags: sanitizeHtml.defaults.allowedTags.concat(['img', 'figure', 'figcaption']),
    allowedAttributes: {
      ...sanitizeHtml.defaults.allowedAttributes,
      img: ['src', 'alt', 'width', 'height', 'loading'],
      '*': ['class'],
    },
  })

  const title    = typeof post.title === 'string' ? post.title : ''
  const category = post.category as { name: string } | null
  const author   = post.author as { name: string } | null

  return (
    <PageLayout>
      <div className="max-w-3xl mx-auto px-5 sm:px-8 py-24">
        {category && (
          <span className="text-xs font-bold uppercase tracking-wider text-[#1271B7]">
            {category.name}
          </span>
        )}
        <h1 className="text-4xl font-black text-[#111a0e] mt-3 mb-4 leading-tight">{title}</h1>
        <div className="flex flex-wrap items-center gap-3 text-sm text-[#6b7c5e] mb-10">
          {post.published_at && (
            <span>
              {new Date(post.published_at as string).toLocaleDateString('en-GB', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
              })}
            </span>
          )}
          {post.reading_time && <span>· {post.reading_time as number} min read</span>}
          {author && <span>· {author.name}</span>}
        </div>
        {post.featured_image && (
          <img
            src={post.featured_image as string}
            alt={title}
            className="w-full rounded-2xl mb-10 aspect-video object-cover"
          />
        )}
        <div
          className="text-[#2d3d1f] text-base leading-relaxed space-y-4 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:text-[#111a0e] [&_h2]:mt-8 [&_h3]:text-xl [&_h3]:font-semibold [&_h3]:text-[#111a0e] [&_a]:text-[#1271B7] [&_a]:underline [&_img]:rounded-xl [&_img]:w-full [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6"
          dangerouslySetInnerHTML={{ __html: safeHtml }}
        />
      </div>
    </PageLayout>
  )
}

export async function generateStaticParams() {
  const apiBase = process.env.LARAVEL_API_URL
  if (!apiBase) return []
  try {
    const res = await fetch(`${apiBase}/api/v1/posts?per_page=100`, {
      next: { revalidate: 3600 },
    })
    if (!res.ok) return []
    const json = await res.json()
    return (json.data as Array<{ slug: string }>).map(p => ({ slug: p.slug }))
  } catch {
    return []
  }
}

export async function generateMetadata({ params }: { params: Promise<{ slug: string }> }) {
  const { slug } = await params
  const post = await fetchPost(slug)

  if (!post) return { title: 'Post Not Found - AgroSAL' }

  const title   = typeof post.title === 'string' ? post.title : ''
  const seoT    = typeof post.seo_title === 'string' ? post.seo_title : ''
  const seoD    = typeof post.seo_description === 'string' ? post.seo_description : ''
  const excerpt = typeof post.excerpt === 'string' ? post.excerpt : ''

  return {
    title: seoT || `${title} - AgroSAL`,
    description: seoD || excerpt,
  }
}
```

- [ ] **Step 2: Verify with a post that has no puck_data**

Create a test post in the admin panel or via tinker. Load `/posts/{slug}`:
- If `content` is set (HTML): sanitized HTML renders with the applied styles.
- If `content` is null: blank page body (correct — nothing to show).
- Page title, category, author, and date appear in the header.

- [ ] **Step 3: Verify with a post that has puck_data**

Open a post in the Puck builder (Task 9), add a HeroSection block, and save. Reload `/posts/{slug}`:
- The Puck-rendered layout replaces the HTML fallback.

- [ ] **Step 4: Run Next.js lint**

```bash
cd agrosal-frontend && npm run lint
```

Fix any TypeScript or ESLint errors before committing.

- [ ] **Step 5: Commit**

```bash
git add agrosal-frontend/src/app/posts/[slug]/page.tsx
git commit -m "feat: add public post detail page with PuckRenderer and HTML fallback"
```

---

### Task 14: Final integration verification

- [ ] **Step 1: Run full Laravel test suite**

```bash
php artisan test
```

Expected: all tests pass (no regressions).

- [ ] **Step 2: End-to-end smoke test — pages**

1. Admin → Pages → click "Edit in Puck" on the Home page
2. New tab opens at `/builder?page=1&token=...`
3. Puck editor loads with existing content
4. Edit a block, click Publish
5. Green "Saved ✓" toast appears
6. Visit `http://localhost:3000/` — change is reflected (may need dev server restart or 60s revalidation)

- [ ] **Step 3: End-to-end smoke test — products**

1. Admin → Products → click "Edit in Puck" on any product
2. Builder opens at `/builder?type=product&id=X&token=...`
3. Add a block, save
4. Visit `http://localhost:3000/products/{slug}` — Puck layout renders

- [ ] **Step 4: End-to-end smoke test — posts**

1. Admin → Posts → create a post via the modal form
2. Click "Edit in Puck" → builder opens at `/builder?type=post&id=X&token=...`
3. Add blocks, save
4. Visit `http://localhost:3000/posts` — post card appears
5. Click card → `/posts/{slug}` — Puck layout renders

- [ ] **Step 5: Final commit**

```bash
git add -A
git commit -m "feat: complete puck editor integration and API-driven public pages"
```
