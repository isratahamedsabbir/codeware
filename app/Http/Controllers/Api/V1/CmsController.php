<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CmsSection;
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
     * GET /api/v1/cms?page=home              -> every active section on that page
     * GET /api/v1/cms?page=home&section=hero -> just that one section
     *
     * Responses are cached in Redis (tagged 'cms', kept forever) so repeat reads
     * never hit the database — CmsSection::flushCache() clears the tag on every
     * create/update/delete/status change, so the cache never serves stale data.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'section' => 'nullable|string',
        ]);

        $locale = $this->resolveLocale($request);
        $page = $validated['page'];
        $section = $validated['section'] ?? null;

        $cacheKey = 'cms:'.$page.':'.($section ?? '_all').':'.$locale;

        $cached = Cache::store('redis')->tags(['cms'])->rememberForever($cacheKey, function () use ($page, $section, $locale) {
            $query = CmsSection::active()->ofPage($page);

            if ($section) {
                $cms = $query->where('section', $section)->first();

                return $cms ? ['found' => true, 'body' => $this->format($cms, $locale)] : ['found' => false];
            }

            $sections = $query->orderBy('id')->get();

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
            'page' => $cms->page,
            'section' => $cms->section,
            'title' => $this->localize($cms->title, $locale),
            'description' => $this->localize($cms->description, $locale),
            'image' => $cms->image,
            'bg_image' => $cms->bg_image,
            'buttons' => collect($cms->buttons ?? [])->map(fn ($button) => [
                'label' => $this->localize($button['label'] ?? null, $locale),
                'color' => $button['color'] ?? null,
                'link' => $button['link'] ?? null,
            ])->values(),
            'cards' => collect($cms->cards ?? [])->map(fn ($card) => [
                'image' => $card['image'] ?? null,
                'title' => $this->localize($card['title'] ?? null, $locale),
                'description' => $this->localize($card['description'] ?? null, $locale),
            ])->values(),
            'metadata' => $cms->metadataMap(),
        ];
    }
}
