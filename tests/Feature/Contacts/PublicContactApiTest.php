<?php

use App\Mail\TemplateDrivenMail;
use Illuminate\Support\Facades\Mail;

it('submits a contact form successfully', function () {
    Mail::fake();

    $payload = [
        'full_name' => 'John Doe',
        'phone_number' => '+8801712345678',
        'email' => 'john@example.com',
        'subject' => 'Product Inquiry',
        'message' => 'I would like to know more about your products.',
    ];

    $this->postJson('/api/v1/contacts', $payload)
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id']])
        ->assertJsonPath('message', 'Your information has been received. Our support team will contact you very soon.');

    $this->assertDatabaseHas('contacts', [
        'email' => 'john@example.com',
        'subject' => 'Product Inquiry',
        'status' => 'unread',
    ]);

    Mail::assertSent(TemplateDrivenMail::class, fn (TemplateDrivenMail $mail) => $mail->hasTo('contact@idesk360.com')
        && $mail->subjectLine === 'Product Inquiry - John Doe'
    );
});

it('submits a request demo form to the support email', function () {
    Mail::fake();

    $this->postJson('/api/v1/request-demo', [
        'full_name' => 'Jane Doe',
        'phone_number' => '+8801712345678',
        'email' => 'jane@example.com',
        'company' => 'Example Ltd',
        'message' => 'Please show us the platform.',
    ])
        ->assertCreated()
        ->assertJsonPath('message', 'Your information has been received. Our support team will contact you very soon.');

    $this->assertDatabaseHas('contacts', [
        'email' => 'jane@example.com',
        'subject' => 'Request Demo',
    ]);

    Mail::assertSent(TemplateDrivenMail::class, fn (TemplateDrivenMail $mail) => $mail->hasTo('contact@idesk360.com')
        && $mail->subjectLine === 'Request Demo - Jane Doe'
    );
});

it('submits a book demo form with preferred date and time', function () {
    Mail::fake();

    $this->postJson('/api/v1/book-demo', [
        'full_name' => 'Jane Doe',
        'phone_number' => '+8801712345678',
        'email' => 'jane@example.com',
        'preferred_date' => '2026-09-15',
        'preferred_time' => '10:30 AM',
        'message' => 'I would like to book a walkthrough.',
    ])
        ->assertCreated()
        ->assertJsonPath('message', 'Your information has been received. Our support team will contact you very soon.');

    $this->assertDatabaseHas('contacts', [
        'email' => 'jane@example.com',
        'subject' => 'Book a Demo',
    ]);

    Mail::assertSent(TemplateDrivenMail::class, fn (TemplateDrivenMail $mail) => $mail->hasTo('contact@idesk360.com')
        && $mail->subjectLine === 'Book a Demo - Jane Doe'
    );
});

it('validates required fields', function () {
    $this->postJson('/api/v1/contacts', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['full_name', 'phone_number', 'email', 'subject', 'message']);
});

it('validates email format', function () {
    $payload = [
        'full_name' => 'John Doe',
        'phone_number' => '+8801712345678',
        'email' => 'not-an-email',
        'subject' => 'Test',
        'message' => 'Test message.',
    ];

    $this->postJson('/api/v1/contacts', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
