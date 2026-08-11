<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name'    => 'required|string|max:255',
            'phone_number' => 'required|string|max:30',
            'email'        => 'required|email|max:255',
            'subject'      => 'required|string|max:255',
            'message'      => 'required|string',
        ]);

        $contact = Contact::create($validated);

        return response()->json([
            'data' => ['id' => $contact->id],
        ], 201);
    }
}
