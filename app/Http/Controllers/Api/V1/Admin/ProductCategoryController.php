<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Support\PageCascade;
use App\Support\Slug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ProductCategory::with('page')->orderBy('sort_order')->get();

        return response()->json([
            'data' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->getTranslations('name'),
                'slug' => $cat->slug,
                'icon' => $cat->icon,
                'sort_order' => $cat->sort_order,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.bn' => 'nullable|string|max:255',
            'slug' => ['nullable', 'string', ...Slug::uniqueRules(null)],
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // The slug lives on the paired Page only — the category has no column for it.
        $slug = $validated['slug'] ?? null;
        unset($validated['slug']);

        $category = ProductCategory::create($validated)->refresh();

        // Keep the paired Page in sync from creation on, same as the Livewire
        // admin form does — otherwise a page created later has a stale slug.
        // refresh() picks up DB-level defaults (e.g. status) the request didn't set.
        $page = Page::updateOrCreate(
            ['type' => 'product_category', 'category_id' => $category->id],
            array_filter([
                'user_id' => $request->user()?->id,
                'title' => $category->getTranslations('name'),
                'slug' => $slug,
                'status' => $category->status,
                'sort_order' => $category->sort_order,
            ], fn ($value) => $value !== null)
        );

        return response()->json(['data' => ['id' => $category->id, 'slug' => $page->slug]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = ProductCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|array',
            'name.en' => 'required_with:name|string|max:255',
            'name.bn' => 'nullable|string|max:255',
            'slug' => ['nullable', 'string', ...Slug::uniqueRules($category->page?->id)],
            'icon' => 'sometimes|nullable|string|max:50',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        // The slug lives on the paired Page only — the category has no column for it.
        $slug = $validated['slug'] ?? null;
        unset($validated['slug']);

        $category->update($validated);

        // Always keep the paired Page in sync (slug especially) regardless of
        // which fields this request touched — a Livewire admin edit syncs
        // unconditionally too, so the two paths can't drift apart.
        // $category->page may already be cached (from the ?->id lookup above
        // for the uniqueness rule) — read the slug off this fresh return value
        // instead of $category->slug, which would resolve the stale cached page.
        $page = Page::updateOrCreate(
            ['type' => 'product_category', 'category_id' => $category->id],
            array_filter([
                'user_id' => $request->user()?->id,
                'title' => $category->getTranslations('name'),
                'slug' => $slug,
                'status' => $category->status,
                'sort_order' => $category->sort_order,
            ], fn ($value) => $value !== null)
        );

        return response()->json(['data' => ['id' => $category->id, 'slug' => $page->slug]]);
    }

    public function destroy(int $id): Response
    {
        $category = ProductCategory::with('page')->findOrFail($id);
        PageCascade::deletePageFor($category, forcePage: true);
        $category->delete();

        return response()->noContent();
    }
}
