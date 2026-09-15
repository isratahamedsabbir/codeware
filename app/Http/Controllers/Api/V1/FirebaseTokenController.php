<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FirebaseToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Registers/removes the FCM device tokens App\Support\Firebase::sendToUser()
 * pushes notifications to. One row per (user, device) — re-registering the
 * same device_id just refreshes its token instead of creating a duplicate.
 */
class FirebaseTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'device_id' => 'required|string|max:255',
        ]);

        $firebaseToken = FirebaseToken::updateOrCreate(
            ['user_id' => $request->user()->id, 'device_id' => $validated['device_id']],
            ['token' => $validated['token'], 'status' => 'active'],
        );

        return response()->json(['data' => $this->format($firebaseToken)], 201);
    }

    public function destroy(Request $request, string $deviceId): Response
    {
        $request->user()->firebaseTokens()->where('device_id', $deviceId)->delete();

        return response()->noContent();
    }

    private function format(FirebaseToken $firebaseToken): array
    {
        return [
            'id' => $firebaseToken->id,
            'device_id' => $firebaseToken->device_id,
            'status' => $firebaseToken->status,
            'created_at' => $firebaseToken->created_at?->toIso8601String(),
            'created_at_display' => $firebaseToken->created_at?->toDisplay(),
            'updated_at' => $firebaseToken->updated_at?->toIso8601String(),
            'updated_at_display' => $firebaseToken->updated_at?->toDisplay(),
        ];
    }
}
