<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $products = Product::withTrashed()
            ->with('category:id,slug')
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->paginate($perPage);

        return response()->json([
            'data' => $products->map(fn ($p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->getTranslations('name'),
                'status' => $p->status,
                'is_featured' => $p->is_featured,
                'sort_order' => $p->sort_order,
                'featured_image' => $p->featured_image,
                'product_category_id' => $p->product_category_id,
                'category' => $p->category,
                'deleted_at' => $p->deleted_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|array',
            'name.en' => 'required|string|max:255',
            'name.bn' => 'nullable|string|max:255',
            'slug' => 'nullable|string|unique:products,slug',
            'product_category_id' => 'nullable|exists:categories,id,type,product',
            'description' => 'nullable|array',
            'description.en' => 'nullable|string',
            'description.bn' => 'nullable|string',
            'featured_image' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'is_featured' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'og_image' => 'nullable|string',
            'seo_title' => 'nullable|array',
            'seo_title.en' => 'nullable|string|max:255',
            'seo_title.bn' => 'nullable|string|max:255',
            'seo_description' => 'nullable|array',
            'seo_description.en' => 'nullable|string',
            'seo_description.bn' => 'nullable|string',
            'faq' => 'nullable|array',
            'puck_data' => 'nullable|array',
            'media_ids' => 'nullable|array',
            'media_ids.*' => 'integer|exists:media_library,id',
        ]);

        $mediaIds = $validated['media_ids'] ?? null;
        unset($validated['media_ids']);

        // SEO fields and OG image live on the paired Page, not on the product itself.
        $seo = collect($validated)->only(['og_image', 'seo_title', 'seo_description'])->all();
        unset($validated['og_image'], $validated['seo_title'], $validated['seo_description']);

        $product = Product::create($validated);

        if ($mediaIds !== null) {
            $sync = collect($mediaIds)
                ->mapWithKeys(fn ($id, $idx) => [$id => ['sort_order' => $idx]])
                ->all();
            $product->gallery()->sync($sync);
        }

        if ($seo !== []) {
            Page::updateOrCreate(
                ['type' => 'product', 'product_id' => $product->id],
                array_filter([
                    'user_id' => $request->user()?->id,
                    'title' => $product->getTranslations('name'),
                    'slug' => $product->slug,
                    'status' => $product->status,
                    'sort_order' => $product->sort_order,
                    'description' => $product->getTranslations('description') ?: null,
                    ...$seo,
                ])
            );
        }

        return response()->json(['data' => ['id' => $product->id, 'slug' => $product->slug]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|array',
            'name.en' => 'required_with:name|string|max:255',
            'name.bn' => 'nullable|string|max:255',
            'slug' => 'nullable|string|unique:products,slug,'.$id,
            'product_category_id' => 'sometimes|nullable|exists:categories,id,type,product',
            'description' => 'sometimes|nullable|array',
            'description.en' => 'nullable|string',
            'description.bn' => 'nullable|string',
            'featured_image' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'is_featured' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
            'og_image' => 'sometimes|nullable|string',
            'seo_title' => 'sometimes|nullable|array',
            'seo_title.en' => 'nullable|string|max:255',
            'seo_title.bn' => 'nullable|string|max:255',
            'seo_description' => 'sometimes|nullable|array',
            'seo_description.en' => 'nullable|string',
            'seo_description.bn' => 'nullable|string',
            'faq' => 'sometimes|nullable|array',
            'puck_data' => 'sometimes|nullable|array',
            'media_ids' => 'sometimes|nullable|array',
            'media_ids.*' => 'integer|exists:media_library,id',
        ]);

        $mediaIds = array_key_exists('media_ids', $validated) ? $validated['media_ids'] : false;
        unset($validated['media_ids']);

        // SEO fields and OG image live on the paired Page, not on the product itself.
        $seo = collect($validated)->only(['og_image', 'seo_title', 'seo_description'])->all();
        unset($validated['og_image'], $validated['seo_title'], $validated['seo_description']);

        $product->update($validated);

        if ($mediaIds !== false) {
            $sync = collect((array) $mediaIds)
                ->mapWithKeys(fn ($id, $idx) => [$id => ['sort_order' => $idx]])
                ->all();
            $product->gallery()->sync($sync);
        }

        if ($seo !== []) {
            Page::updateOrCreate(
                ['type' => 'product', 'product_id' => $product->id],
                array_filter([
                    'user_id' => $request->user()?->id,
                    'title' => $product->getTranslations('name'),
                    'slug' => $product->slug,
                    'status' => $product->status,
                    'sort_order' => $product->sort_order,
                    'description' => $product->getTranslations('description') ?: null,
                    ...$seo,
                ])
            );
        }

        return response()->json(['data' => ['id' => $product->id, 'slug' => $product->slug]]);
    }

    public function destroy(int $id): Response
    {
        Product::findOrFail($id)->delete();

        return response()->noContent();
    }
}
