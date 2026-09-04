<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\PageCascade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PageController extends Controller
{
    public function index(): JsonResponse
    {
        $pages = Page::withTrashed()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($page) => [
                'id' => $page->id,
                'type' => $page->type,
                'slug' => $page->slug,
                'title' => $page->title,
                'status' => $page->status,
                'template' => $page->template,
                'sort_order' => $page->sort_order,
                'deleted_at' => $page->deleted_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $pages]);
    }

    public function show(int $id): JsonResponse
    {
        $page = Page::withTrashed()->with('revisions')->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $page->id,
                'type' => $page->type,
                'product_id' => $page->product_id,
                'post_id' => $page->post_id,
                'category_id' => $page->category_id,
                'slug' => $page->slug,
                'title' => $page->title,
                'content' => $page->content,
                'puck_data' => $page->puck_data,
                'status' => $page->status,
                'template' => $page->template,
                'sort_order' => $page->sort_order,
                'seo_title' => $page->seo_title,
                'seo_description' => $page->seo_description,
                'og_title' => $page->og_title,
                'og_description' => $page->og_description,
                'no_index' => $page->no_index,
                'no_follow' => $page->no_follow,
                'constant' => $page->constant,
                'deleted_at' => $page->deleted_at?->toIso8601String(),
                'revisions' => $page->revisions->map(fn ($r) => [
                    'id' => $r->id,
                    'created_at' => $r->created_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|array',
            'title.en' => 'required|string|max:255',
            'title.bn' => 'nullable|string|max:255',
            'slug' => 'nullable|string|unique:pages,slug',
            'status' => 'sometimes|in:active,inactive',
            'template' => 'sometimes|nullable|string|max:100',
            'puck_data' => 'nullable|array',
            'constant' => 'sometimes|array',
            'constant.*.key' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_]*$/'],
            'constant.*.type' => 'nullable|in:textarea,file',
            'constant.*.value' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = $validated['status'] ?? 'inactive';
        $validated['template'] = $validated['template'] ?? 'puck';

        $page = Page::create($validated);

        return response()->json(['data' => ['id' => $page->id, 'slug' => $page->slug]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $page = Page::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|array',
            'title.en' => 'required_with:title|string|max:255',
            'title.bn' => 'nullable|string|max:255',
            'content' => 'sometimes|array',
            'content.en' => 'nullable|string',
            'content.bn' => 'nullable|string',
            'seo_title' => 'sometimes|nullable|string|max:255',
            'seo_description' => 'sometimes|nullable|string',
            'og_title' => 'sometimes|nullable|string|max:255',
            'og_description' => 'sometimes|nullable|string',
            'no_index' => 'sometimes|boolean',
            'no_follow' => 'sometimes|boolean',
            'puck_data' => 'sometimes|nullable|array',
            'status' => 'sometimes|in:active,inactive',
            'template' => 'sometimes|nullable|string|max:100',
            'slug' => 'sometimes|string|unique:pages,slug,'.$page->id,
            'constant' => 'sometimes|array',
            'constant.*.key' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9_]*$/'],
            'constant.*.type' => 'nullable|in:textarea,file',
            'constant.*.value' => 'nullable|string|max:1000',
        ]);

        $page->update($validated);

        return response()->json(['data' => ['id' => $page->id, 'slug' => $page->slug]]);
    }

    public function destroy(int $id): Response
    {
        $page = Page::findOrFail($id);
        PageCascade::deleteEntityFor($page);
        $page->delete();

        return response()->noContent();
    }
}
