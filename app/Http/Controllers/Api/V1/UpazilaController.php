<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Upazila;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpazilaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 100);
        $locale  = in_array($request->query('locale'), ['en', 'bn'], true) ? $request->query('locale') : 'en';

        $upazilas = Upazila::query()
            ->when($request->has('district_id'), fn ($q) => $q->where('district_id', $request->query('district_id')))
            ->orderBy('sort_order')
            ->paginate($perPage);

        return response()->json([
            'data' => $upazilas->map(fn ($u) => [
                'id'          => $u->id,
                'district_id' => $u->district_id,
                'name'        => $u->getTranslation('name', $locale, useFallbackLocale: true),
                'slug'        => $u->slug,
                'sort_order'  => $u->sort_order,
            ]),
            'meta' => [
                'current_page' => $upazilas->currentPage(),
                'last_page'    => $upazilas->lastPage(),
                'per_page'     => $upazilas->perPage(),
                'total'        => $upazilas->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $upazila = Upazila::with('district')->findOrFail($id);

        return response()->json([
            'data' => [
                'id'          => $upazila->id,
                'district_id' => $upazila->district_id,
                'name'        => $upazila->getTranslations('name'),
                'slug'        => $upazila->slug,
                'sort_order'  => $upazila->sort_order,
                'district'    => $upazila->district ? ['id' => $upazila->district->id, 'name' => $upazila->district->getTranslations('name'), 'slug' => $upazila->district->slug] : null,
            ],
        ]);
    }
}
