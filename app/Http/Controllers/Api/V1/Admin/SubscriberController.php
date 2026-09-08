<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubscriberController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $subscribers = Subscriber::query()
            ->orderByDesc('created_at')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('search'), fn ($q, $search) => $q->where('email', 'like', "%{$search}%"))
            ->paginate($perPage);

        return response()->json([
            'data' => $subscribers->map(fn ($subscriber) => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'status' => $subscriber->status,
                'created_at' => $subscriber->created_at?->toIso8601String(),
                'created_at_display' => $subscriber->created_at?->toDisplay(),
                'updated_at' => $subscriber->updated_at?->toIso8601String(),
                'updated_at_display' => $subscriber->updated_at?->toDisplay(),
            ]),
            'meta' => [
                'current_page' => $subscribers->currentPage(),
                'last_page' => $subscribers->lastPage(),
                'per_page' => $subscribers->perPage(),
                'total' => $subscribers->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $subscriber = Subscriber::findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'status' => $subscriber->status,
                'created_at' => $subscriber->created_at?->toIso8601String(),
                'created_at_display' => $subscriber->created_at?->toDisplay(),
                'updated_at' => $subscriber->updated_at?->toIso8601String(),
                'updated_at_display' => $subscriber->updated_at?->toDisplay(),
            ],
        ]);
    }

    public function destroy(int $id): Response
    {
        Subscriber::findOrFail($id)->delete();

        return response()->noContent();
    }
}
