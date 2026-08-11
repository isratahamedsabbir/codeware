<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 100);
        $locale  = in_array($request->query('locale'), ['en', 'bn'], true) ? $request->query('locale') : 'en';

        $districts = District::query()
            ->orderBy('sort_order')
            ->paginate($perPage);

        return response()->json([
            'data' => $districts->map(fn ($d) => [
                'id'         => $d->id,
                'name'       => $d->getTranslation('name', $locale, useFallbackLocale: true),
                'slug'       => $d->slug,
                'sort_order' => $d->sort_order,
            ]),
            'meta' => [
                'current_page' => $districts->currentPage(),
                'last_page'    => $districts->lastPage(),
                'per_page'     => $districts->perPage(),
                'total'        => $districts->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $district = District::with('upazilas')->findOrFail($id);

        return response()->json([
            'data' => [
                'id'         => $district->id,
                'name'       => $district->getTranslations('name'),
                'slug'       => $district->slug,
                'sort_order' => $district->sort_order,
                'upazilas'   => $district->upazilas->map(fn ($u) => [
                    'id'         => $u->id,
                    'name'       => $u->getTranslations('name'),
                    'slug'       => $u->slug,
                    'sort_order' => $u->sort_order,
                ]),
            ],
        ]);
    }
}
