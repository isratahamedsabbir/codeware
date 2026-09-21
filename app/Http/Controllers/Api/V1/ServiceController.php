<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Setting;
use App\Support\ContentCache;
use App\Support\Locale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceController extends Controller
{
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');

        return is_string($locale) && Locale::isSupported($locale) ? $locale : Locale::default();
    }

    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);
        $perPage = max(1, min((int) $request->query('per_page', Setting::perPage()), 100));

        // Only the first page of an unfiltered browse is worth caching — search
        // results and deeper pages are request-specific and rarely repeated.
        if ((int) $request->query('page', 1) === 1 && ! $request->query('search')) {
            return response()->json(ContentCache::remember(
                "api:services:{$locale}:{$perPage}",
                fn () => $this->listPayload(Service::active()->orderBy('sort_order')->paginate($perPage), $locale),
            ));
        }

        $services = Service::active()
            ->orderBy('sort_order')
            ->when($request->query('search'), fn ($q, $search) => $q->where("name->{$locale}", 'like', "%{$search}%"))
            ->paginate($perPage);

        return response()->json($this->listPayload($services, $locale));
    }

    /**
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    private function listPayload(LengthAwarePaginator $services, string $locale): array
    {
        return [
            'data' => collect($services->items())->map(fn ($s) => $this->formatService($s, $locale))->values()->all(),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ],
        ];
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $service = Service::active()->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => $this->formatService($service, $locale, withDetail: true),
        ]);
    }

    private function formatService(Service $service, string $locale, bool $withDetail = false): array
    {
        $data = [
            'id' => $service->id,
            'slug' => $service->slug,
            'name' => $service->getTranslation('name', $locale, useFallbackLocale: true),
            'price' => (float) $service->price,
            'featured_image' => $service->featured_image,
        ];

        if ($withDetail) {
            $data['description'] = $service->getTranslation('description', $locale, useFallbackLocale: true);
        }

        return $data;
    }
}
