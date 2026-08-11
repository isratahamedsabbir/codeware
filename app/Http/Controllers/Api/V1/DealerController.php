<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerController extends Controller
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

        $lat    = $request->query('lat') !== null ? (float) $request->query('lat') : null;
        $lng    = $request->query('lng') !== null ? (float) $request->query('lng') : null;
        $radius = min((float) $request->query('radius', 50), 200);

        $query = Dealer::active()
            ->with('categories')
            ->when($request->query('district'), fn ($q, $v) =>
                $q->where('district', 'like', "%{$v}%"))
            ->when($request->query('upazila'), fn ($q, $v) =>
                $q->where('upazila', 'like', "%{$v}%"))
            ->when($request->query('category'), fn ($q, $slug) =>
                $q->whereHas('categories', fn ($c) => $c->where('slug', $slug)))
            ->when($request->query('search'), fn ($q, $search) =>
                $q->where(fn ($sub) => $sub
                    ->where("name->{$locale}", 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%")));

        if ($lat !== null && $lng !== null) {
            $latDelta = $radius / 111.0;
            $lngDelta = $radius / (111.0 * cos(deg2rad($lat)));

            $dealers = $query
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereBetween('latitude',  [$lat - $latDelta, $lat + $latDelta])
                ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
                ->get()
                ->map(function (Dealer $dealer) use ($lat, $lng) {
                    $dealer->distance_km = $this->haversine(
                        $lat, $lng,
                        (float) $dealer->latitude,
                        (float) $dealer->longitude
                    );
                    return $dealer;
                })
                ->filter(fn (Dealer $d) => $d->distance_km <= $radius)
                ->sortBy('distance_km')
                ->values();

            return response()->json([
                'data' => $dealers->map(fn ($d) => $this->formatDealer($d, $locale, withDistance: true)),
                'meta' => ['total' => $dealers->count()],
            ]);
        }

        $dealers = $query->orderBy('sort_order')->paginate($perPage);

        return response()->json([
            'data' => $dealers->map(fn ($d) => $this->formatDealer($d, $locale)),
            'meta' => [
                'current_page' => $dealers->currentPage(),
                'last_page'    => $dealers->lastPage(),
                'per_page'     => $dealers->perPage(),
                'total'        => $dealers->total(),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $dealer = Dealer::active()->with('categories')->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => $this->formatDealer($dealer, $locale),
        ]);
    }

    private function formatDealer(Dealer $dealer, string $locale, bool $withDistance = false): array
    {
        return [
            'id'          => $dealer->id,
            'name'        => $dealer->getTranslation('name', $locale, useFallbackLocale: true),
            'slug'        => $dealer->slug,
            'address'     => $dealer->getTranslation('address', $locale, useFallbackLocale: true),
            'district'    => $dealer->district,
            'upazila'     => $dealer->upazila,
            'phone'       => $dealer->phone,
            'email'       => $dealer->email,
            'latitude'    => $dealer->latitude !== null ? (float) $dealer->latitude : null,
            'longitude'   => $dealer->longitude !== null ? (float) $dealer->longitude : null,
            //'status'      => $dealer->status,
            //'sort_order'  => $dealer->sort_order,
            //'distance_km' => $withDistance ? round($dealer->distance_km, 2) : null,
            'categories'  => $dealer->categories->map(fn ($cat) => [
                'id'   => $cat->id,
                'name' => $cat->getTranslation('name', $locale, useFallbackLocale: true),
                'slug' => $cat->slug,
                'icon' => $cat->icon,
            ])->values(),
        ];
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $earthRadius * 2 * asin(sqrt($a));
    }
}
