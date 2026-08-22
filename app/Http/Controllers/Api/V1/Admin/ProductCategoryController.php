<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\ProductCategory;
use App\Support\PageCascade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ProductCategory::orderBy('sort_order')->get();

        return response()->json([
            'data' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->getTranslations('name'),
                'slug' => $cat->slug,
                'description' => $cat->getTranslations('description'),
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
            'slug' => 'nullable|string|unique:categories,slug,NULL,id,type,product',
            'description' => 'nullable|array',
            'description.en' => 'nullable|string',
            'description.bn' => 'nullable|string',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = ProductCategory::create($validated)->refresh();

        // Keep the paired Page in sync from creation on, same as the Livewire
        // admin form does — otherwise a page created later has a stale slug.
        // refresh() picks up DB-level defaults (e.g. status) the request didn't set.
        Page::updateOrCreate(
            ['type' => 'product_category', 'category_id' => $category->id],
            [
                'user_id' => $request->user()?->id,
                'title' => $category->getTranslations('name'),
                'slug' => $category->slug,
                'status' => $category->status,
                'sort_order' => $category->sort_order,
                'description' => $category->getTranslations('description') ?: null,
            ]
        );

        return response()->json(['data' => ['id' => $category->id, 'slug' => $category->slug]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = ProductCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|array',
            'name.en' => 'required_with:name|string|max:255',
            'name.bn' => 'nullable|string|max:255',
            'slug' => 'nullable|string|unique:categories,slug,'.$id.',id,type,product',
            'description' => 'sometimes|nullable|array',
            'description.en' => 'nullable|string',
            'description.bn' => 'nullable|string',
            'icon' => 'sometimes|nullable|string|max:50',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $category->update($validated);

        // Always keep the paired Page in sync (slug especially) regardless of
        // which fields this request touched — a Livewire admin edit syncs
        // unconditionally too, so the two paths can't drift apart.
        Page::updateOrCreate(
            ['type' => 'product_category', 'category_id' => $category->id],
            [
                'user_id' => $request->user()?->id,
                'title' => $category->getTranslations('name'),
                'slug' => $category->slug,
                'status' => $category->status,
                'sort_order' => $category->sort_order,
                'description' => $category->getTranslations('description') ?: null,
            ]
        );

        return response()->json(['data' => ['id' => $category->id, 'slug' => $category->slug]]);
    }

    public function destroy(int $id): Response
    {
        $category = ProductCategory::with('page')->findOrFail($id);
        PageCascade::deletePageFor($category, forcePage: true);
        $category->delete();

        return response()->noContent();
    }
}
