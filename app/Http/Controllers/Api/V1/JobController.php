<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    private function resolveLocale(Request $request): string
    {
        $locale = $request->query('locale');
        return in_array($locale, ['en', 'bn'], true) ? $locale : 'en';
    }

    public function index(Request $request): JsonResponse
    {
        $locale  = $this->resolveLocale($request);
        $perPage = max(1, min((int) $request->query('per_page', 10), 50));

        $jobs = Job::active()
            ->with('department')
            ->when($request->query('department'), fn ($q, $slug) =>
                $q->whereHas('department', fn ($d) => $d->where('slug', $slug)))
            ->when($request->query('search'), fn ($q, $search) =>
                $q->where("title->{$locale}", 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->paginate($perPage);

        return response()->json([
            'data' => $jobs->map(fn ($job) => $this->formatJob($job, $locale)),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page'    => $jobs->lastPage(),
                'per_page'     => $jobs->perPage(),
                'total'        => $jobs->total(),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = $this->resolveLocale($request);

        $job = Job::active()->with('department')->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => $this->formatJob($job, $locale, withDescription: true),
        ]);
    }

    private function formatJob(Job $job, string $locale, bool $withDescription = false): array
    {
        $data = [
            'id'         => $job->id,
            'slug'       => $job->slug,
            'title'      => $job->getTranslation('title', $locale, useFallbackLocale: true),
            'position'   => $job->position,
            'vacancy'    => $job->vacancy,
            'deadline'   => $job->deadline?->format('Y-m-d'),
            'location'   => $job->location,
            //'status'     => $job->status,
            'created_at' => $job->created_at->toIso8601String(),
            'department' => $job->department ? [
                'id'   => $job->department->id,
                'name' => $job->department->getTranslation('name', $locale, useFallbackLocale: true),
                'slug' => $job->department->slug,
            ] : null,
        ];

        if ($withDescription) {
            $data['description'] = $job->getTranslation('description', $locale, useFallbackLocale: true);
        }

        return $data;
    }
}
