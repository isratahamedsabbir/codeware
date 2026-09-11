<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Product;
use App\Support\PageCascade;
use App\Support\Slug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $products = Product::withTrashed()
            ->with(['categories.page', 'page'])
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
                'categories' => $p->categories,
                'deleted_at' => $p->deleted_at?->toIso8601String(),
                'deleted_at_display' => $p->deleted_at?->toDisplay(),
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
            'slug' => ['nullable', 'string', ...Slug::uniqueRules(null)],
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id,type,product',
            'description' => 'nullable|array',
            'description.en' => 'nullable|string',
            'description.bn' => 'nullable|string',
            'featured_image' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'is_featured' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'og_image' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'faq' => 'nullable|array',
            'puck_data' => 'nullable|array',
            'media_ids' => 'nullable|array',
            'media_ids.*' => 'integer|exists:media_library,id',
        ]);

        $mediaIds = $validated['media_ids'] ?? null;
        unset($validated['media_ids']);

        $categoryIds = $validated['category_ids'] ?? [];
        unset($validated['category_ids']);

        // SEO fields, OG image, and the puck-builder content all live on the paired
        // Page, not on the product itself.
        $pageFields = collect($validated)->only(['og_image', 'seo_title', 'seo_description', 'puck_data'])->all();
        unset($validated['og_image'], $validated['seo_title'], $validated['seo_description'], $validated['puck_data']);

        // The slug lives on the paired Page only — the product has no column for it.
        $slug = $validated['slug'] ?? null;
        unset($validated['slug']);

        $product = Product::create($validated)->refresh();

        $product->categories()->sync($categoryIds);

        if ($mediaIds !== null) {
            $sync = collect($mediaIds)
                ->mapWithKeys(fn ($id, $idx) => [$id => ['sort_order' => $idx]])
                ->all();
            $product->gallery()->sync($sync);
        }

        // Always keep the paired Page in sync (slug especially) regardless of
        // whether this request touched any SEO fields — a Livewire admin edit
        // syncs unconditionally too, so the two paths can't drift apart.
        $page = Page::updateOrCreate(
            ['type' => 'product', 'product_id' => $product->id],
            array_filter([
                'user_id' => $request->user()?->id,
                'title' => $product->getTranslations('name'),
                'slug' => $slug,
                'status' => $product->status,
                'sort_order' => $product->sort_order,
                'description' => $product->getTranslations('description') ?: null,
                ...$pageFields,
            ])
        );

        return response()->json(['data' => ['id' => $product->id, 'slug' => $page->slug]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|array',
            'name.en' => 'required_with:name|string|max:255',
            'name.bn' => 'nullable|string|max:255',
            'slug' => ['nullable', 'string', ...Slug::uniqueRules($product->page?->id)],
            'category_ids' => 'sometimes|nullable|array',
            'category_ids.*' => 'integer|exists:categories,id,type,product',
            'description' => 'sometimes|nullable|array',
            'description.en' => 'nullable|string',
            'description.bn' => 'nullable|string',
            'featured_image' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'is_featured' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
            'og_image' => 'sometimes|nullable|string',
            'seo_title' => 'sometimes|nullable|string|max:255',
            'seo_description' => 'sometimes|nullable|string',
            'faq' => 'sometimes|nullable|array',
            'puck_data' => 'sometimes|nullable|array',
            'media_ids' => 'sometimes|nullable|array',
            'media_ids.*' => 'integer|exists:media_library,id',
        ]);

        $mediaIds = array_key_exists('media_ids', $validated) ? $validated['media_ids'] : false;
        unset($validated['media_ids']);

        $categoryIds = array_key_exists('category_ids', $validated) ? $validated['category_ids'] : false;
        unset($validated['category_ids']);

        // SEO fields, OG image, and the puck-builder content all live on the paired
        // Page, not on the product itself.
        $pageFields = collect($validated)->only(['og_image', 'seo_title', 'seo_description', 'puck_data'])->all();
        unset($validated['og_image'], $validated['seo_title'], $validated['seo_description'], $validated['puck_data']);

        // The slug lives on the paired Page only — the product has no column for it.
        $slug = $validated['slug'] ?? null;
        unset($validated['slug']);

        $product->update($validated);

        if ($categoryIds !== false) {
            $product->categories()->sync((array) $categoryIds);
        }

        if ($mediaIds !== false) {
            $sync = collect((array) $mediaIds)
                ->mapWithKeys(fn ($id, $idx) => [$id => ['sort_order' => $idx]])
                ->all();
            $product->gallery()->sync($sync);
        }

        // Always keep the paired Page in sync (slug especially) regardless of
        // whether this request touched any SEO fields — a Livewire admin edit
        // syncs unconditionally too, so the two paths can't drift apart.
        // $product->page may already be cached (from the ?->id lookup above for
        // the uniqueness rule) — read the slug off this fresh return value
        // instead of $product->slug, which would resolve the stale cached page.
        $page = Page::updateOrCreate(
            ['type' => 'product', 'product_id' => $product->id],
            array_filter([
                'user_id' => $request->user()?->id,
                'title' => $product->getTranslations('name'),
                'slug' => $slug,
                'status' => $product->status,
                'sort_order' => $product->sort_order,
                'description' => $product->getTranslations('description') ?: null,
                ...$pageFields,
            ])
        );

        return response()->json(['data' => ['id' => $product->id, 'slug' => $page->slug]]);
    }

    public function destroy(int $id): Response
    {
        $product = Product::with('page')->findOrFail($id);
        PageCascade::deletePageFor($product);
        $product->delete();

        return response()->noContent();
    }
}
