<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');
        return in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
    }

    public function index(Request $request): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $departments = Department::withCount(['jobs' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name->en')
            ->get();

        return response()->json([
            'data' => $departments->map(fn ($dept) => [
                'id'         => $dept->id,
                'name'       => $dept->getTranslation('name', $locale, useFallbackLocale: true),
                'slug'       => $dept->slug,
                'jobs_count' => $dept->jobs_count,
            ]),
        ]);
    }
}
