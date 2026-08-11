<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DistrictController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 100);

        $districts = District::withTrashed()
            ->withCount('upazilas')
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $districts->map(fn ($d) => [
                'id'             => $d->id,
                'name'           => $d->getTranslations('name'),
                'slug'           => $d->slug,
                'sort_order'     => $d->sort_order,
                'upazilas_count' => $d->upazilas_count,
                'deleted_at'     => $d->deleted_at?->toIso8601String(),
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
        $district = District::withTrashed()->with('upazilas')->findOrFail($id);

        return response()->json([
            'data' => [
                'id'         => $district->id,
                'name'       => $district->getTranslations('name'),
                'slug'       => $district->slug,
                'sort_order' => $district->sort_order,
                'deleted_at' => $district->deleted_at?->toIso8601String(),
                'upazilas'   => $district->upazilas->map(fn ($u) => [
                    'id'         => $u->id,
                    'name'       => $u->getTranslations('name'),
                    'slug'       => $u->slug,
                    'sort_order' => $u->sort_order,
                ]),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => 'required|array',
            'name.en'    => 'required|string|max:255',
            'name.bn'    => 'nullable|string|max:255',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $district = District::create($validated);

        return response()->json(['data' => ['id' => $district->id]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $district = District::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'name'       => 'sometimes|array',
            'name.en'    => 'required_with:name|string|max:255',
            'name.bn'    => 'nullable|string|max:255',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $district->update($validated);

        return response()->json(['data' => ['id' => $district->id]]);
    }

    public function destroy(int $id): Response
    {
        District::findOrFail($id)->delete();
        return response()->noContent();
    }
}
