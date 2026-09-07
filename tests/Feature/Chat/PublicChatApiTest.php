<?php

use App\Events\MessageSent;
use App\Mail\ChatOtpMail;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('requires a name and a valid email before sending an otp', function () {
    $this->postJson('/api/v1/chat/otp/request', ['name' => '', 'email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email']);
});

it('emails a 6-digit otp', function () {
    Mail::fake();

    $this->postJson('/api/v1/chat/otp/request', ['name' => 'Jane Doe', 'email' => 'jane@example.com'])
        ->assertOk();

    Mail::assertSent(ChatOtpMail::class, fn ($mail) => $mail->hasTo('jane@example.com') && preg_match('/^\d{6}$/', $mail->code));

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

it('rejects requesting another otp within the cooldown window', function () {
    Mail::fake();

    $this->postJson('/api/v1/chat/otp/request', ['name' => 'Jane Doe', 'email' => 'jane@example.com'])->assertOk();

    $this->postJson('/api/v1/chat/otp/request', ['name' => 'Jane Doe', 'email' => 'jane@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    Mail::assertSent(ChatOtpMail::class, 1);
});

it('rejects an incorrect otp', function () {
    Mail::fake();

    $this->postJson('/api/v1/chat/otp/request', ['name' => 'Jane Doe', 'email' => 'jane@example.com'])->assertOk();

    $this->postJson('/api/v1/chat/otp/verify', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'otp' => '000000',
    ])->assertUnprocessable()->assertJsonValidationErrors(['otp']);

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

it('verifying the correct otp creates a real account, starts a conversation, and returns a resume token', function () {
    Mail::fake();

    $this->postJson('/api/v1/chat/otp/request', ['name' => 'Jane Doe', 'email' => 'jane@example.com'])->assertOk();

    $code = Cache::get('chat-widget-otp:jane@example.com');
    expect($code)->not->toBeNull();

    $response = $this->postJson('/api/v1/chat/otp/verify', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'otp' => $code,
    ])->assertOk()->assertJsonStructure(['data' => ['token', 'conversation_id', 'admin_name', 'guest', 'messages']]);

    $guest = User::where('email', 'jane@example.com')->sole();

    expect($guest->name)->toBe('Jane Doe')
        ->and((bool) $guest->is_admin)->toBeFalse()
        ->and($guest->email_verified_at)->not->toBeNull()
        ->and($response->json('data.admin_name'))->toBe($this->admin->name);

    $conversation = Conversation::between($guest, $this->admin);
    expect($response->json('data.conversation_id'))->toBe($conversation->id);
});

it('does not let chat support start when no admin account exists', function () {
    User::where('is_admin', true)->delete();
    Mail::fake();

    $this->postJson('/api/v1/chat/otp/request', ['name' => 'Jane Doe', 'email' => 'jane@example.com'])->assertOk();
    $code = Cache::get('chat-widget-otp:jane@example.com');

    $this->postJson('/api/v1/chat/otp/verify', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'otp' => $code,
    ])->assertStatus(503);

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

it('rejects messages without a valid token', function () {
    $this->getJson('/api/v1/chat/messages')->assertUnauthorized();

    $this->postJson('/api/v1/chat/messages', ['body' => 'hi'])->assertUnauthorized();

    $this->withHeader('Authorization', 'Bearer not-a-real-token')
        ->getJson('/api/v1/chat/messages')
        ->assertUnauthorized();
});

it('rejects a token that points at an admin account', function () {
    $token = encrypt(['conversation_id' => 1, 'user_id' => $this->admin->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/chat/messages')
        ->assertUnauthorized();
});

it('sends a message as the guest and broadcasts it to the admin', function () {
    Event::fake([MessageSent::class]);

    $guest = User::factory()->create(['is_admin' => false]);
    $conversation = Conversation::between($guest, $this->admin);
    $token = encrypt(['conversation_id' => $conversation->id, 'user_id' => $guest->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/chat/messages', ['body' => 'Hi, I need help'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Hi, I need help')
        ->assertJsonPath('data.sender_id', $guest->id);

    $message = ChatMessage::sole();

    expect($message->body)->toBe('Hi, I need help')
        ->and($message->sender_id)->toBe($guest->id)
        ->and($message->conversation_id)->toBe($conversation->id);

    Event::assertDispatched(MessageSent::class, fn ($event) => $event->message->id === $message->id);
});

it('lists messages for the authenticated guest, optionally since a given id', function () {
    $guest = User::factory()->create(['is_admin' => false]);
    $conversation = Conversation::between($guest, $this->admin);
    $token = encrypt(['conversation_id' => $conversation->id, 'user_id' => $guest->id]);

    $first = $conversation->messages()->create(['sender_id' => $guest->id, 'body' => 'first']);
    $second = $conversation->messages()->create(['sender_id' => $this->admin->id, 'body' => 'second']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/chat/messages')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/chat/messages?since_id={$first->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $second->id);
});
