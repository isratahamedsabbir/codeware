<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');
        return in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
    }

    public function index(Request $request): JsonResponse
    {
        $locale  = $this->resolveLocale($request);
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));

        $videos = Video::orderBy('sort_order')->paginate($perPage);

        return response()->json([
            'data' => $videos->map(fn ($video) => $this->formatVideo($video, $locale)),
            'meta' => [
                'current_page' => $videos->currentPage(),
                'last_page'    => $videos->lastPage(),
                'per_page'     => $videos->perPage(),
                'total'        => $videos->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $video  = Video::published()->findOrFail($id);

        return response()->json([
            'data' => $this->formatVideo($video, $locale),
        ]);
    }

    private function formatVideo(Video $video, string $locale): array
    {
        return [
            'id'           => $video->id,
            'title'        => $video->getTranslation('title', $locale, useFallbackLocale: true),
            'youtube_link' => $video->youtube_link,
            'thumbnail'    => $video->thumbnail,
            //'sort_order'   => $video->sort_order,
        ];
    }
}
