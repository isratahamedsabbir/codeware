<?php

use App\Livewire\Admin\Contacts\Index;
use App\Livewire\Admin\Settings\Index as SettingsIndex;
use App\Livewire\Frontend\ContactForm;
use App\Mail\TemplateDrivenMail;
use App\Models\Contact;
use App\Models\Setting;
use App\Models\User;
use App\Support\EnvFile;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
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

it('sends a new contact to Telegram when that switch is on, and never when off', function () {
    Mail::fake();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    config(['services.telegram.bot_token' => '123:abc', 'services.telegram.chat_id' => '-100']);

    $payload = [
        'full_name' => 'Jane Doe',
        'phone_number' => '0123456789',
        'email' => 'jane@example.com',
        'subject' => 'Hello',
        'message' => 'Test message',
    ];

    $this->postJson('/api/v1/contacts', $payload)->assertCreated();
    Http::assertNothingSent();

    Setting::set('contact_telegram_enabled', '1');

    $this->postJson('/api/v1/contacts', $payload)->assertCreated();
    Http::assertSent(fn ($request) => str_contains($request->url(), 'bot123:abc/sendMessage')
        && $request['chat_id'] === '-100'
        && str_contains($request['text'], 'Jane Doe'));
});

it('still stores the contact when Telegram is unreachable', function () {
    Mail::fake();
    Http::fake(fn () => throw new ConnectionException('down'));
    config(['services.telegram.bot_token' => '123:abc', 'services.telegram.chat_id' => '-100']);
    Setting::set('contact_telegram_enabled', '1');

    $this->postJson('/api/v1/contacts', [
        'full_name' => 'Jane Doe',
        'phone_number' => '0123456789',
        'email' => 'jane@example.com',
        'subject' => 'Hello',
        'message' => 'Test message',
    ])->assertCreated();

    expect(Contact::count())->toBe(1);
});

it('saves the Telegram details to .env and never to the settings table', function () {
    $envPath = tempnam(sys_get_temp_dir(), 'env');
    file_put_contents($envPath, "APP_NAME=Test\n");
    EnvFile::$pathOverride = $envPath;

    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test(SettingsIndex::class)
        ->set('telegramBotToken', '123:secret')
        ->set('telegramChatId', '-100')
        ->call('save');

    expect(EnvFile::get('TELEGRAM_BOT_TOKEN'))->toBe('123:secret')
        ->and(EnvFile::get('TELEGRAM_CHAT_ID'))->toBe('-100')
        ->and(Setting::whereIn('key', ['telegram_bot_token', 'telegram_chat_id'])->exists())->toBeFalse();

    $this->getJson('/api/v1/settings')->assertDontSee('123:secret');

    EnvFile::$pathOverride = null;
    @unlink($envPath);
});

it('moves a token an earlier version stored as a setting into .env', function () {
    $envPath = tempnam(sys_get_temp_dir(), 'env');
    file_put_contents($envPath, "APP_NAME=Test\n");
    EnvFile::$pathOverride = $envPath;

    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
    Setting::set('telegram_bot_token', '123:old');
    Setting::set('telegram_chat_id', '-7');

    Livewire::actingAs($admin)->test(SettingsIndex::class)
        ->assertSet('telegramBotToken', '123:old')
        ->assertSet('telegramChatId', '-7');

    expect(Setting::where('key', 'telegram_bot_token')->exists())->toBeFalse();

    EnvFile::$pathOverride = null;
    @unlink($envPath);
});

it('also sends a contact made through the frontend contact form to Telegram', function () {
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    config(['services.telegram.bot_token' => '123:abc', 'services.telegram.chat_id' => '-100']);
    Setting::set('contact_telegram_enabled', '1');

    Livewire::test(ContactForm::class)
        ->set('full_name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('subject', 'Hello')
        ->set('message', 'From the form')
        ->call('send')
        ->assertSet('sent', true);

    Http::assertSent(fn ($request) => str_contains($request['text'], 'From the form'));
});

it('marks a contact read when the admin opens it', function () {
    $this->seed(RolePermissionSeeder::class);
    $admin = User::factory()->admin()->create();
    $contact = Contact::factory()->create(['status' => 'unread']);

    Livewire::actingAs($admin)->test(Index::class)
        ->call('showContact', $contact->id)
        ->assertSet('viewingContactId', $contact->id);

    expect($contact->fresh()->status)->toBe('read');
});
