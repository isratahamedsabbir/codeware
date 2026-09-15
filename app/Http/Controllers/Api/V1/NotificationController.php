<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * Read-side API for the authenticated user's database notifications (see
 * Notifiable on User) — the API equivalent of the admin bell
 * (Livewire\Admin\Notifications\Bell) for clients that can't run Livewire
 * (mobile app, Next.js frontend). Notifications themselves (e.g. AdminAlert)
 * are still created server-side via the normal ->notify() call; this only
 * lists/reads/deletes what's already there.
 */
class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', Setting::perPage()), 100);

        $notifications = $request->user()->notifications()
            ->when($request->query('status') === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->when($request->query('status') === 'read', fn ($q) => $q->whereNotNull('read_at'))
            ->paginate($perPage);

        return response()->json([
            'data' => $notifications->map(fn (DatabaseNotification $n) => $this->format($n)),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ],
        ]);
    }

    public function read(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['data' => $this->format($notification->fresh())]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['data' => null]);
    }

    public function destroy(Request $request, string $id): Response
    {
        $request->user()->notifications()->whereKey($id)->delete();

        return response()->noContent();
    }

    private function format(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => Str::afterLast($notification->type, '\\'),
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'read_at_display' => $notification->read_at?->toDisplay(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'created_at_display' => $notification->created_at?->toDisplay(),
        ];
    }
}
