<?php

use App\Models\Contact;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminAlert;
use App\Support\ContactAlerts;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

function contactPayload(array $over = []): array
{
    return array_merge([
        'full_name' => 'A B', 'phone_number' => '123', 'email' => 'a@b.co',
        'subject' => 'Hi', 'message' => 'Hello',
    ], $over);
}

beforeEach(function () {
    Cache::flush();
    Notification::fake();
});

it('throttles contacts to 5 per minute per IP', function () {
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/v1/contacts', contactPayload())->assertCreated();
    }

    $this->postJson('/api/v1/contacts', contactPayload())->assertTooManyRequests();
});

it('rejects messages over 5000 characters', function () {
    $this->postJson('/api/v1/contacts', contactPayload(['message' => str_repeat('a', 5001)]))
        ->assertJsonValidationErrors('message');
});

it('silently drops honeypot submissions', function () {
    $this->postJson('/api/v1/contacts', contactPayload(['website' => 'http://spam']))->assertCreated();

    expect(Contact::count())->toBe(0);
});

it('requires a captcha token once keys are configured', function () {
    config(['services.captcha.provider' => 'recaptcha', 'services.recaptcha.site_key' => 's', 'services.recaptcha.secret_key' => 'k']);
    Http::fake(fn ($request) => Http::response(['success' => ($request->data()['response'] ?? null) === 'good', 'score' => 0.9]));

    $this->postJson('/api/v1/contacts', contactPayload())->assertStatus(422);
    $this->postJson('/api/v1/contacts', contactPayload(['captcha_token' => 'bad']))->assertStatus(422);

    $this->postJson('/api/v1/contacts', contactPayload(['captcha_token' => 'good']))->assertCreated();
});

it('batches admin notifications to one per minute', function () {
    $admin = User::factory()->create();
    Role::findOrCreate('admin');
    $admin->assignRole('admin');

    foreach (range(1, 4) as $i) {
        Contact::create(contactPayload());
    }

    Notification::assertSentToTimes($admin, AdminAlert::class, 1);

    Cache::forget('contacts:alert-window');
    ContactAlerts::flushDue();

    Notification::assertSentToTimes($admin, AdminAlert::class, 2);
});

it('answers a wrong-email order lookup exactly like a missing order, and throttles it', function () {
    $order = Order::factory()->create(['customer_email' => 'real@x.co']);

    $wrong = $this->getJson("/api/v1/orders/{$order->order_number}?email=other@x.co");
    $missing = $this->getJson('/api/v1/orders/ORD-NOPE0000?email=other@x.co');

    expect($wrong->status())->toBe(404)->and($missing->status())->toBe(404)
        ->and($wrong->json('message'))->toBe($missing->json('message'));

    foreach (range(1, 9) as $i) {
        $this->getJson("/api/v1/orders/{$order->order_number}?email=other@x.co");
    }

    $this->getJson("/api/v1/orders/{$order->order_number}?email=real@x.co")->assertTooManyRequests();
});
