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
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');

        return in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
    }

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

        $locale = $this->resolveLocale($request);
        $pageSlug = $validated['page'];
        $name = $validated['name'] ?? null;

        $cacheKey = 'cms:'.$pageSlug.':'.($name ?? '_all').':'.$locale;

        $cached = Cache::store('redis')->tags(['cms'])->rememberForever($cacheKey, function () use ($pageSlug, $name, $locale) {
            $page = Page::where('slug', $pageSlug)->first();

            if (! $page) {
                return ['found' => false];
            }

            $query = CmsSection::active()->ofPage($page->id);

            if ($name) {
                $cms = $query->where('name', $name)->first();

                return $cms ? ['found' => true, 'body' => $this->format($cms, $locale)] : ['found' => false];
            }

            $sections = $query->orderBy('sort_order')->orderBy('id')->get();

            return ['found' => true, 'body' => $sections->map(fn (CmsSection $cms) => $this->format($cms, $locale))->all()];
        });

        if (! $cached['found']) {
            return response()->json(['message' => 'Section not found.'], 404);
        }

        return response()->json(['data' => $cached['body']]);
    }

    /**
     * Resolves a {en, bn} pair to a plain string for the requested locale,
     * falling back to English when the requested locale is empty.
     */
    private function localize(?array $value, string $locale): ?string
    {
        if (! $value) {
            return null;
        }

        return $value[$locale] ?: ($value['en'] ?? null);
    }

    private function format(CmsSection $cms, string $locale): array
    {
        return [
            'id' => $cms->id,
            'page_id' => $cms->page_id,
            'name' => $cms->name,
            'title' => $this->localize($cms->title, $locale),
            'description' => $this->localize($cms->description, $locale),
            'image' => $cms->image,
            'bg_image' => $cms->bg_image,
            'cards' => collect($cms->cards ?? [])->map(fn ($card) => [
                'image' => $card['image'] ?? null,
                'title' => $this->localize($card['title'] ?? null, $locale),
                'description' => $this->localize($card['description'] ?? null, $locale),
            ])->values(),
            'metadata' => $cms->metadataMap(),
        ];
    }
}
