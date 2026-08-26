<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CmsSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => 'required|string',
            'section' => 'nullable|string',
        ]);

        $locale = $this->resolveLocale($request);

        $query = CmsSection::active()->ofPage($validated['page']);

        if ($section = $validated['section'] ?? null) {
            $cms = $query->where('section', $section)->first();

            if (! $cms) {
                return response()->json(['message' => 'Section not found.'], 404);
            }

            return response()->json(['data' => $this->format($cms, $locale)]);
        }

        $sections = $query->orderBy('id')->get();

        return response()->json([
            'data' => $sections->map(fn (CmsSection $cms) => $this->format($cms, $locale))->values(),
        ]);
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
        ];
    }
}
