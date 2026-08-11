<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
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
                'id'          => $cat->id,
                'name'        => $cat->getTranslations('name'),
                'slug'        => $cat->slug,
                'description' => $cat->getTranslations('description'),
                'icon'        => $cat->icon,
                'sort_order'  => $cat->sort_order,
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'            => 'required|array',
            'name.en'         => 'required|string|max:255',
            'name.bn'         => 'nullable|string|max:255',
            'slug'            => 'nullable|string|unique:product_categories,slug',
            'description'     => 'nullable|array',
            'description.en'  => 'nullable|string',
            'description.bn'  => 'nullable|string',
            'icon'            => 'nullable|string|max:50',
            'sort_order'      => 'nullable|integer|min:0',
        ]);

        $category = ProductCategory::create($validated);

        return response()->json(['data' => ['id' => $category->id, 'slug' => $category->slug]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = ProductCategory::findOrFail($id);

        $validated = $request->validate([
            'name'            => 'sometimes|array',
            'name.en'         => 'required_with:name|string|max:255',
            'name.bn'         => 'nullable|string|max:255',
            'slug'            => 'nullable|string|unique:product_categories,slug,' . $id,
            'description'     => 'sometimes|nullable|array',
            'description.en'  => 'nullable|string',
            'description.bn'  => 'nullable|string',
            'icon'            => 'sometimes|nullable|string|max:50',
            'sort_order'      => 'sometimes|integer|min:0',
        ]);

        $category->update($validated);

        return response()->json(['data' => ['id' => $category->id, 'slug' => $category->slug]]);
    }

    public function destroy(int $id): Response
    {
        ProductCategory::findOrFail($id)->delete();
        return response()->noContent();
    }
}
