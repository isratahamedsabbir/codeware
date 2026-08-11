<?php

use App\Models\Contact;

it('submits a contact form successfully', function () {
    $payload = [
        'full_name'    => 'John Doe',
        'phone_number' => '+8801712345678',
        'email'        => 'john@example.com',
        'subject'      => 'Product Inquiry',
        'message'      => 'I would like to know more about your products.',
    ];

    $this->postJson('/api/v1/contacts', $payload)
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id']]);

    $this->assertDatabaseHas('contacts', [
        'email'   => 'john@example.com',
        'subject' => 'Product Inquiry',
        'status'  => 'unread',
    ]);
});

it('validates required fields', function () {
    $this->postJson('/api/v1/contacts', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['full_name', 'phone_number', 'email', 'subject', 'message']);
});

it('validates email format', function () {
    $payload = [
        'full_name'    => 'John Doe',
        'phone_number' => '+8801712345678',
        'email'        => 'not-an-email',
        'subject'      => 'Test',
        'message'      => 'Test message.',
    ];

    $this->postJson('/api/v1/contacts', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
