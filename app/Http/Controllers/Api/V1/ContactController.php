<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\TemplateDrivenMail;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:30',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        return $this->createContact($validated);
    }

    public function requestDemo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:30',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        return $this->createContact([
            ...$validated,
            'subject' => 'Request Demo',
            'view_name' => 'emails.request-demo',
        ]);
    }

    public function bookDemo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:30',
            'email' => 'required|email|max:255',
            'company' => 'nullable|string|max:255',
            'preferred_date' => 'required|date',
            'preferred_time' => 'required|string|max:50',
            'message' => 'required|string',
        ]);

        return $this->createContact([
            ...$validated,
            'subject' => 'Book a Demo',
            'view_name' => 'emails.book-demo',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createContact(array $data): JsonResponse
    {
        $contact = Contact::create([
            'full_name' => $data['full_name'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        $emailDetails = [
            'Full name' => $data['full_name'],
            'Phone' => $data['phone_number'],
            'Email' => $data['email'],
            'Company' => $data['company'] ?? null,
            'Preferred date' => $data['preferred_date'] ?? null,
            'Preferred time' => $data['preferred_time'] ?? null,
            'Message' => $data['message'],
        ];

        $body = collect($emailDetails)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value, $label) => '<strong>'.e($label).':</strong> '.nl2br(e((string) $value)))
            ->implode('<br>');

        Mail::to('contact@idesk360.com')->send(new TemplateDrivenMail(
            $data['subject'].' - '.$data['full_name'],
            $body,
            $data['view_name'] ?? 'emails.template-driven',
        ));

        return response()->json([
            'data' => [
                'id' => $contact->id,
                'full_name' => $contact->full_name,
                'phone_number' => $contact->phone_number,
                'email' => $contact->email,
                'company' => $data['company'] ?? null,
                'subject' => $contact->subject,
                'preferred_date' => $data['preferred_date'] ?? null,
                'preferred_time' => $data['preferred_time'] ?? null,
                'message' => $contact->message,
                'status' => $contact->status,
                'created_at' => $contact->created_at?->toIso8601String(),
                'updated_at' => $contact->updated_at?->toIso8601String(),
            ],
            'message' => 'Your information has been received. Our support team will contact you very soon.',
        ], 201);
    }
}
