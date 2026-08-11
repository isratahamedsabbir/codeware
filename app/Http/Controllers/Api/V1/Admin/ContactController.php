<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $contacts = Contact::withTrashed()
            ->orderByDesc('created_at')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->paginate($perPage);

        return response()->json([
            'data' => $contacts->map(fn ($contact) => [
                'id'           => $contact->id,
                'full_name'    => $contact->full_name,
                'phone_number' => $contact->phone_number,
                'email'        => $contact->email,
                'subject'      => $contact->subject,
                'message'      => $contact->message,
                'status'       => $contact->status,
                'created_at'   => $contact->created_at?->toIso8601String(),
                'updated_at'   => $contact->updated_at?->toIso8601String(),
                'deleted_at'   => $contact->deleted_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $contacts->currentPage(),
                'last_page'    => $contacts->lastPage(),
                'per_page'     => $contacts->perPage(),
                'total'        => $contacts->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $contact = Contact::withTrashed()->findOrFail($id);

        return response()->json([
            'data' => [
                'id'           => $contact->id,
                'full_name'    => $contact->full_name,
                'phone_number' => $contact->phone_number,
                'email'        => $contact->email,
                'subject'      => $contact->subject,
                'message'      => $contact->message,
                'status'       => $contact->status,
                'created_at'   => $contact->created_at?->toIso8601String(),
                'updated_at'   => $contact->updated_at?->toIso8601String(),
                'deleted_at'   => $contact->deleted_at?->toIso8601String(),
            ],
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $contact = Contact::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:read,unread',
        ]);

        $contact->update($validated);

        return response()->json(['data' => ['id' => $contact->id]]);
    }

    public function destroy(int $id): Response
    {
        Contact::findOrFail($id)->delete();
        return response()->noContent();
    }
}
