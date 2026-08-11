<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VideoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $videos = Video::withTrashed()
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $videos->map(fn ($video) => [
                'id'           => $video->id,
                'title'        => $video->getTranslations('title'),
                'youtube_link' => $video->youtube_link,
                'thumbnail'    => $video->thumbnail,
                'status'       => $video->status,
                'sort_order'   => $video->sort_order,
                'deleted_at'   => $video->deleted_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page'    => $videos->lastPage(),
                'per_page'     => $videos->perPage(),
                'total'        => $videos->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $video = Video::withTrashed()->findOrFail($id);

        return response()->json([
            'data' => [
                'id'           => $video->id,
                'title'        => $video->getTranslations('title'),
                'youtube_link' => $video->youtube_link,
                'thumbnail'    => $video->thumbnail,
                'status'       => $video->status,
                'sort_order'   => $video->sort_order,
                'deleted_at'   => $video->deleted_at?->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => 'required|array',
            'title.en'    => 'required|string|max:255',
            'title.bn'    => 'nullable|string|max:255',
            'youtube_link' => 'required|string|max:500',
            'thumbnail'   => 'nullable|string|max:500',
            'status'      => 'sometimes|in:draft,published',
            'sort_order'  => 'sometimes|integer|min:0',
        ]);

        $validated['status'] = $validated['status'] ?? 'draft';

        $video = Video::create($validated);

        return response()->json(['data' => ['id' => $video->id]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $video = Video::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'title'       => 'sometimes|array',
            'title.en'    => 'required_with:title|string|max:255',
            'title.bn'    => 'nullable|string|max:255',
            'youtube_link' => 'sometimes|string|max:500',
            'thumbnail'   => 'sometimes|nullable|string|max:500',
            'status'      => 'sometimes|in:draft,published',
            'sort_order'  => 'sometimes|integer|min:0',
        ]);

        $video->update($validated);

        return response()->json(['data' => ['id' => $video->id]]);
    }

    public function destroy(int $id): Response
    {
        Video::findOrFail($id)->delete();
        return response()->noContent();
    }
}
