<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $subscriber = Subscriber::firstOrNew(['email' => $validated['email']]);

        if ($subscriber->exists && $subscriber->status === 'subscribed') {
            return response()->json([
                'data' => [
                    'id' => $subscriber->id,
                    'email' => $subscriber->email,
                    'status' => $subscriber->status,
                ],
                'message' => 'You are already subscribed.',
            ]);
        }

        $subscriber->status = 'subscribed';
        $subscriber->save();

        return response()->json([
            'data' => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'status' => $subscriber->status,
                'created_at' => $subscriber->created_at?->toIso8601String(),
                'created_at_display' => $subscriber->created_at?->toDisplay(),
            ],
            'message' => 'Thanks for subscribing!',
        ], 201);
    }
}
