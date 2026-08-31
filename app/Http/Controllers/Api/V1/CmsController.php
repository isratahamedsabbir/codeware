<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CmsSection;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CmsController extends Controller
{
    /**
     * GET /api/v1/cms?page=home                -> every active section on that page
     * GET /api/v1/cms?page=home&name=hero      -> just that one named section
     *
     * Responses are cached in Redis (tagged 'cms', kept forever) so repeat reads
     * never hit the database — CmsSection::flushCache() clears the tag on every
     * create/update/delete/status change, so the cache never serves stale data.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'name' => 'nullable|string',
        ]);

        $pageSlug = $validated['page'];
        $name = $validated['name'] ?? null;

        $cacheKey = 'cms:'.$pageSlug.':'.($name ?? '_all');

        $cached = Cache::store('redis')->tags(['cms'])->rememberForever($cacheKey, function () use ($pageSlug, $name) {
            $page = Page::where('slug', $pageSlug)->first();

            if (! $page) {
                return ['found' => false];
            }

            $query = CmsSection::active()->ofPage($page->id);

            if ($name) {
                $cms = $query->where('name', $name)->first();

                return $cms ? ['found' => true, 'body' => $this->format($cms)] : ['found' => false];
            }

            $sections = $query->orderBy('sort_order')->orderBy('id')->get();

            return ['found' => true, 'body' => $sections->map(fn (CmsSection $cms) => $this->format($cms))->all()];
        });

        if (! $cached['found']) {
            return response()->json(['message' => 'Section not found.'], 404);
        }

        return response()->json(['data' => $cached['body']]);
    }

    private function format(CmsSection $cms): array
    {
        return [
            'id' => $cms->id,
            'page_id' => $cms->page_id,
            'name' => $cms->name,
            'cards' => collect($cms->cards ?? [])->map(fn ($card) => [
                'image' => $card['image'] ?? null,
                'title' => $card['title'] ?? null,
                'description' => $card['description'] ?? null,
            ])->values(),
            'metadata' => $cms->metadataMap(),
        ];
    }
}
