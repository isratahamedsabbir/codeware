<?php

use App\Livewire\Admin\Contacts\Index;
use App\Mail\TemplateDrivenMail;
use App\Models\Contact;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('falls back to the default support email when contact_email is not configured', function () {
    Mail::fake();

    $this->postJson('/api/v1/contacts', [
        'full_name' => 'John Doe',
        'phone_number' => '+8801712345678',
        'email' => 'john@example.com',
        'subject' => 'Product Inquiry',
        'message' => 'I would like to know more about your products.',
    ])->assertCreated();

    Mail::assertSent(TemplateDrivenMail::class, fn (TemplateDrivenMail $mail) => $mail->hasTo('contact@idesk360.com'));
});

it('sends contact form submissions to the configured contact_email setting', function () {
    Mail::fake();
    Setting::set('contact_email', 'support@codeware.test');

    $this->postJson('/api/v1/contacts', [
        'full_name' => 'John Doe',
        'phone_number' => '+8801712345678',
        'email' => 'john@example.com',
        'subject' => 'Product Inquiry',
        'message' => 'I would like to know more about your products.',
    ])->assertCreated();

    Mail::assertSent(TemplateDrivenMail::class, fn (TemplateDrivenMail $mail) => $mail->hasTo('support@codeware.test'));
});

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

it('stores a contact but sends no email when contact emails are switched off', function () {
    Mail::fake();
    Setting::set('contact_email_enabled', '0');

    $this->postJson('/api/v1/contacts', [
        'full_name' => 'Jane Doe',
        'phone_number' => '0123456789',
        'email' => 'jane@example.com',
        'subject' => 'Hello',
        'message' => 'Test message',
    ])->assertCreated();

    expect(Contact::count())->toBe(1);
    Mail::assertNothingSent();
});

it('toggles contact emails from the admin contacts page', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->assertSet('emailEnabled', true)
        ->set('emailEnabled', false);

    expect(Setting::contactEmailEnabled())->toBeFalse();
});
