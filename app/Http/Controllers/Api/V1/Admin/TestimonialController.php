<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TestimonialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $testimonials = Testimonial::withTrashed()
            ->with('product')
            ->when($request->has('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->has('type'), fn ($q) => $q->where('type', $request->query('type')))
            ->orderBy('sort_order')
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return response()->json([
            'data' => $testimonials->map(fn ($t) => [
                'id'         => $t->id,
                'image'      => $t->image,
                'name'       => $t->getTranslations('name'),
                'comment'    => $t->getTranslations('comment'),
                'location'   => $t->location,
                'type'       => $t->type,
                'product_id' => $t->product_id,
                'product'    => $t->product ? ['id' => $t->product->id, 'name' => $t->product->getTranslations('name')] : null,
                'status'     => $t->status,
                'sort_order' => $t->sort_order,
                'deleted_at' => $t->deleted_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $testimonials->currentPage(),
                'last_page'    => $testimonials->lastPage(),
                'per_page'     => $testimonials->perPage(),
                'total'        => $testimonials->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $testimonial = Testimonial::withTrashed()->with('product')->findOrFail($id);

        return response()->json([
            'data' => [
                'id'         => $testimonial->id,
                'image'      => $testimonial->image,
                'name'       => $testimonial->getTranslations('name'),
                'comment'    => $testimonial->getTranslations('comment'),
                'location'   => $testimonial->location,
                'type'       => $testimonial->type,
                'product_id' => $testimonial->product_id,
                'product'    => $testimonial->product ? ['id' => $testimonial->product->id, 'name' => $testimonial->product->getTranslations('name')] : null,
                'status'     => $testimonial->status,
                'sort_order' => $testimonial->sort_order,
                'deleted_at' => $testimonial->deleted_at?->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image'      => 'nullable|string|max:500',
            'name'       => 'required|array',
            'name.en'    => 'required|string|max:255',
            'name.bn'    => 'nullable|string|max:255',
            'comment'    => 'required|array',
            'comment.en' => 'required|string',
            'comment.bn' => 'nullable|string',
            'location'   => 'nullable|string|max:255',
            'type'       => 'nullable|string|max:100',
            'product_id' => 'nullable|integer|exists:products,id',
            'status'     => 'sometimes|in:published,draft',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $validated['status'] = $validated['status'] ?? 'active';

        $testimonial = Testimonial::create($validated);

        return response()->json(['data' => ['id' => $testimonial->id]], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $testimonial = Testimonial::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'image'      => 'sometimes|nullable|string|max:500',
            'name'       => 'sometimes|array',
            'name.en'    => 'required_with:name|string|max:255',
            'name.bn'    => 'nullable|string|max:255',
            'comment'    => 'sometimes|array',
            'comment.en' => 'required_with:comment|string',
            'comment.bn' => 'nullable|string',
            'location'   => 'sometimes|nullable|string|max:255',
            'type'       => 'sometimes|nullable|string|max:100',
            'product_id' => 'sometimes|nullable|integer|exists:products,id',
            'status'     => 'sometimes|in:active,inactive',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $testimonial->update($validated);

        return response()->json(['data' => ['id' => $testimonial->id]]);
    }

    public function destroy(int $id): Response
    {
        Testimonial::findOrFail($id)->delete();
        return response()->noContent();
    }
}
