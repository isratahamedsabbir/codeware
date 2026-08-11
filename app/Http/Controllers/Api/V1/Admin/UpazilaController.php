<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Upazila;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UpazilaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 50), 100);

        $upazilas = Upazila::withTrashed()
            ->with('district')
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $upazilas->map(fn ($u) => [
                'id'          => $u->id,
                'district_id' => $u->district_id,
                'district'    => $u->district ? ['id' => $u->district->id, 'name' => $u->district->getTranslations('name'), 'slug' => $u->district->slug] : null,
                'name'        => $u->getTranslations('name'),
                'slug'        => $u->slug,
                'sort_order'  => $u->sort_order,
                'deleted_at'  => $u->deleted_at?->toIso8601String(),
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
        $upazila = Upazila::withTrashed()->with('district')->findOrFail($id);

        return response()->json([
            'data' => [
                'id'          => $upazila->id,
                'district_id' => $upazila->district_id,
                'district'    => $upazila->district ? ['id' => $upazila->district->id, 'name' => $upazila->district->getTranslations('name'), 'slug' => $upazila->district->slug] : null,
                'name'        => $upazila->getTranslations('name'),
                'slug'        => $upazila->slug,
                'sort_order'  => $upazila->sort_order,
                'deleted_at'  => $upazila->deleted_at?->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'district_id' => 'required|exists:districts,id',
            'name'        => 'required|array',
            'name.en'     => 'required|string|max:255',
            'name.bn'     => 'nullable|string|max:255',
            'sort_order'  => 'sometimes|integer|min:0',
        ]);

        $upazila = Upazila::create($validated);

        return response()->json(['data' => ['id' => $upazila->id]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $upazila = Upazila::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'district_id' => 'sometimes|exists:districts,id',
            'name'        => 'sometimes|array',
            'name.en'     => 'required_with:name|string|max:255',
            'name.bn'     => 'nullable|string|max:255',
            'sort_order'  => 'sometimes|integer|min:0',
        ]);

        $upazila->update($validated);

        return response()->json(['data' => ['id' => $upazila->id]]);
    }

    public function destroy(int $id): Response
    {
        Upazila::findOrFail($id)->delete();
        return response()->noContent();
    }
}
