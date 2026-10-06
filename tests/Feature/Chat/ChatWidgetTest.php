<?php

use App\Events\MessageSent;
use App\Livewire\Frontend\ChatWidget;
use App\Mail\ChatOtpMail;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $this->admin = User::factory()->admin()->create();
});

it('renders empty output when the chat widget is disabled in settings', function () {
    Setting::set('chat_widget_enabled', false);

    Livewire::test(ChatWidget::class)->assertDontSee('Open chat');
});

it('renders the chat bubble when the chat widget is enabled in settings', function () {
    Setting::set('chat_widget_enabled', true);

    Livewire::test(ChatWidget::class)->assertSee('Open chat');
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
        ->and($guest->hasRole('admin'))->toBeFalse()
        ->and($guest->email_verified_at)->not->toBeNull();

    $conversation = Conversation::between($guest, $this->admin);
    expect($conversation->isParticipant($guest))->toBeTrue()
        ->and($conversation->isParticipant($this->admin))->toBeTrue();
});

it('does not let chat support start when no admin account exists', function () {
    User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->delete();
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
    $guest = User::factory()->create();
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

    $guest = User::factory()->create();
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

it('applies the chat widget color from settings to the widget', function () {
    Setting::set('chat_widget_enabled', true);
    Setting::set('chat_widget_color', '#ff5500');

    Livewire::test(ChatWidget::class)->assertSeeHtml('--color-primary: #ff5500');
});

it('keeps the site primary color when no chat widget color is set', function () {
    Setting::set('chat_widget_enabled', true);
    Setting::set('chat_widget_color', '');

    Livewire::test(ChatWidget::class)->assertDontSeeHtml('--color-primary:');
});

it('sends the real widget to a click, not to a page load', function () {
    // The two runtimes this feature used to cost every visitor on every page
    // now ride along with the fragment, so the endpoint has to hand back all
    // three things or the panel arrives unable to do anything: the component's
    // markup, Flux (for the widget's own form controls) and Livewire (for
    // everything else).
    Setting::set('chat_widget_enabled', true);

    $response = $this->getJson(route('chat-widget.fragment'))->assertOk();

    $payload = $response->json();

    expect($payload['enabled'])->toBeTrue()
        ->and($payload['html'])->toContain('data-chat-toggle')
        ->and($payload['scripts'])->toHaveCount(2)
        ->and($payload['scripts'][0])->toContain('flux')
        ->and($payload['scripts'][1])->toContain('livewire');
});

it('answers the click with nothing when the widget has been switched off since', function () {
    // The bubble is in the page, so the setting is only consulted at the moment
    // it is clicked. Reporting it disabled lets the placeholder take the button
    // away, rather than fetching a component that would render an empty frame.
    Setting::set('chat_widget_enabled', false);

    $this->getJson(route('chat-widget.fragment'))
        ->assertOk()
        ->assertExactJson(['enabled' => false]);
});

it('puts a button on the page that can fetch itself a panel', function () {
    // Deliberately not the component: what ships on page load is a static
    // button pointing at the endpoint, and the assertion that it is *not* the
    // component is the half that saves 386 KB of JavaScript.
    Setting::set('chat_widget_enabled', true);

    $partial = file_get_contents(resource_path('views/frontend/partials/_chat-widget.blade.php'));

    expect($partial)->toContain('data-chat-widget-host')
        ->and($partial)->toContain("route('chat-widget.fragment')")
        ->and($partial)->toContain('data-chat-toggle')
        ->and($partial)->not->toContain('<livewire:');
});

it('does not let a visitor point the widget at another conversation from the browser', function () {
    $victim = User::factory()->create();
    $conversation = Conversation::between($victim, $this->admin);
    ChatMessage::create(['conversation_id' => $conversation->id, 'sender_id' => $victim->id, 'body' => 'private']);

    $widget = Livewire::test(ChatWidget::class);

    expect(fn () => $widget->set('conversationId', $conversation->id))
        ->toThrow(Exception::class, 'Cannot update locked property');
    expect(fn () => $widget->set('guestUserId', $this->admin->id))
        ->toThrow(Exception::class, 'Cannot update locked property');

    $widget->assertDontSee('private');
});
