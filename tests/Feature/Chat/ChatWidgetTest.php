<?php

use App\Events\MessageSent;
use App\Livewire\Frontend\ChatWidget;
use App\Mail\ChatOtpMail;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('requires a name and a valid email before sending an otp', function () {
    Livewire::test(ChatWidget::class)
        ->set('name', '')
        ->set('email', 'not-an-email')
        ->call('requestOtp')
        ->assertHasErrors(['name', 'email']);
});

it('emails a 6-digit otp and moves to the otp step', function () {
    Mail::fake();

    Livewire::test(ChatWidget::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->call('requestOtp')
        ->assertHasNoErrors()
        ->assertSet('step', 'otp');

    Mail::assertSent(ChatOtpMail::class, fn ($mail) => $mail->hasTo('jane@example.com') && preg_match('/^\d{6}$/', $mail->code));

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

it('rejects requesting another otp within the cooldown window', function () {
    Mail::fake();

    $component = Livewire::test(ChatWidget::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->call('requestOtp');

    $component->call('requestOtp')->assertHasErrors(['email']);

    Mail::assertSent(ChatOtpMail::class, 1);
});

it('rejects an incorrect otp', function () {
    Mail::fake();

    Livewire::test(ChatWidget::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->call('requestOtp')
        ->set('otp', '000000')
        ->call('verifyOtp')
        ->assertHasErrors(['otp']);

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

it('verifying the correct otp creates a real account and starts a conversation with the admin', function () {
    Mail::fake();

    $component = Livewire::test(ChatWidget::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->call('requestOtp');

    $code = Cache::get('chat-widget-otp:jane@example.com');
    expect($code)->not->toBeNull();

    $component->set('otp', $code)
        ->call('verifyOtp')
        ->assertHasNoErrors()
        ->assertSet('step', 'chat')
        ->assertDispatched('chat-widget-verified');

    $guest = User::where('email', 'jane@example.com')->sole();

    expect($guest->name)->toBe('Jane Doe')
        ->and((bool) $guest->is_admin)->toBeFalse()
        ->and($guest->email_verified_at)->not->toBeNull();

    $conversation = Conversation::between($guest, $this->admin);
    expect($conversation->isParticipant($guest))->toBeTrue()
        ->and($conversation->isParticipant($this->admin))->toBeTrue();
});

it('does not let chat support start when no admin account exists', function () {
    User::where('is_admin', true)->delete();
    Mail::fake();

    $component = Livewire::test(ChatWidget::class)
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->call('requestOtp');

    $code = Cache::get('chat-widget-otp:jane@example.com');

    $component->set('otp', $code)
        ->call('verifyOtp')
        ->assertHasErrors(['otp']);

    expect(User::where('email', 'jane@example.com')->exists())->toBeFalse();
});

it('resumes a verified conversation from an encrypted token without re-verifying', function () {
    $guest = User::factory()->create(['is_admin' => false]);
    $conversation = Conversation::between($guest, $this->admin);

    $token = encrypt(['conversation_id' => $conversation->id, 'user_id' => $guest->id]);

    Livewire::test(ChatWidget::class)
        ->call('resume', $token)
        ->assertSet('step', 'chat')
        ->assertSet('conversationId', $conversation->id)
        ->assertSet('guestUserId', $guest->id);
});

it('ignores a resume token that is invalid, tampered, or points at an admin account', function () {
    Livewire::test(ChatWidget::class)
        ->call('resume', 'not-a-real-token')
        ->assertSet('step', 'form');

    $conversation = Conversation::between(User::factory()->create(), $this->admin);
    $adminToken = encrypt(['conversation_id' => $conversation->id, 'user_id' => $this->admin->id]);

    Livewire::test(ChatWidget::class)
        ->call('resume', $adminToken)
        ->assertSet('step', 'form');
});

it('sends a message as the guest and broadcasts it to the admin', function () {
    Event::fake([MessageSent::class]);

    $guest = User::factory()->create(['is_admin' => false]);
    $conversation = Conversation::between($guest, $this->admin);
    $token = encrypt(['conversation_id' => $conversation->id, 'user_id' => $guest->id]);

    Livewire::test(ChatWidget::class)
        ->call('resume', $token)
        ->set('messageBody', 'Hi, I need help')
        ->call('sendMessage')
        ->assertHasNoErrors()
        ->assertSet('messageBody', '');

    $message = ChatMessage::sole();

    expect($message->body)->toBe('Hi, I need help')
        ->and($message->sender_id)->toBe($guest->id)
        ->and($message->conversation_id)->toBe($conversation->id);

    Event::assertDispatched(MessageSent::class, fn ($event) => $event->message->id === $message->id);
});
