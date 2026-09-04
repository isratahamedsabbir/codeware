<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CmsSection;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsController extends Controller
{
    /**
     * GET /api/v1/cms?page=home                -> every active section on that page
     * GET /api/v1/cms?page=home&name=hero      -> just that one named section
     *
     * Reads from CmsSection::cachedForPage() — the same Redis-cached (tag
     * 'cms', kept forever) lookup the admin's cms_cards()/cms_content()
     * helpers use, so there's one cache to invalidate (CmsSection::flushCache(),
     * fired on every create/update/delete/status change) instead of two.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'name' => 'nullable|string',
        ]);

        $pageSlug = $validated['page'];
        $name = $validated['name'] ?? null;

        $page = Page::where('slug', $pageSlug)->first();

        if (! $page) {
            return response()->json(['message' => 'Section not found.'], 404);
        }

        $sections = CmsSection::cachedForPage($page->id);

        if ($name) {
            $cms = $sections->firstWhere('name', $name);

            return $cms
                ? response()->json(['data' => $this->format($cms)])
                : response()->json(['message' => 'Section not found.'], 404);
        }

        return response()->json(['data' => $sections->map(fn (CmsSection $cms) => $this->format($cms))->values()]);
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
            'content' => $cms->contentMap(),
        ];
    }
}
