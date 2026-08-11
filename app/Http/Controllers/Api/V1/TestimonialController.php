<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestimonialController extends Controller
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

        $testimonials = Testimonial::published()
            ->with('product')
            ->when($request->has('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->orderBy('sort_order')
            ->paginate($perPage);

        return response()->json([
            'data' => $testimonials->map(fn ($t) => $this->formatTestimonial($t, $locale)),
            'meta' => [
                'current_page' => $testimonials->currentPage(),
                'last_page'    => $testimonials->lastPage(),
                'per_page'     => $testimonials->perPage(),
                'total'        => $testimonials->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $locale      = $this->resolveLocale($request);
        $testimonial = Testimonial::published()->with('product')->findOrFail($id);

        return response()->json([
            'data' => $this->formatTestimonial($testimonial, $locale),
        ]);
    }

    private function formatTestimonial(Testimonial $testimonial, string $locale): array
    {
        return [
            'id'         => $testimonial->id,
            'image'      => $testimonial->image,
            'name'       => $testimonial->getTranslation('name', $locale, useFallbackLocale: true),
            'comment'    => $testimonial->getTranslation('comment', $locale, useFallbackLocale: true),
            'location'   => $testimonial->location,
            'type'       => $testimonial->type,
            'product_id' => $testimonial->product_id,
            'product'    => $testimonial->product
                ? ['id' => $testimonial->product->id, 'name' => $testimonial->product->getTranslation('name', $locale, useFallbackLocale: true)]
                : null,
            //'sort_order' => $testimonial->sort_order,
        ];
    }
}
