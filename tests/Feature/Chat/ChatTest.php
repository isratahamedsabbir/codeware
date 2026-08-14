<?php

use App\Events\MessageSent;
use App\Livewire\Admin\Chat\Index as ChatIndex;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->userA = User::factory()->create(['is_admin' => true]);
    $this->userB = User::factory()->create(['is_admin' => false]);
    $this->userB->assignRole('admin');
    $this->userC = User::factory()->create(['is_admin' => false]);
    $this->userC->assignRole('staff');
});

it('finds or creates the same conversation regardless of participant order', function () {
    $ab = Conversation::between($this->userA, $this->userB);
    $ba = Conversation::between($this->userB, $this->userA);

    expect($ab->id)->toBe($ba->id)
        ->and(Conversation::count())->toBe(1);
});

it('lets every admin tier including staff open the chat page', function () {
    foreach ([$this->userA, $this->userB, $this->userC] as $user) {
        $this->actingAs($user);
        $this->get(route('admin.chat'))->assertOk();
    }
});

it('starts a conversation with another user and prevents starting one with yourself', function () {
    $this->actingAs($this->userA);

    Livewire::test(ChatIndex::class)
        ->call('startConversationWith', $this->userB->id)
        ->assertSet('conversationId', Conversation::between($this->userA, $this->userB)->id);

    Livewire::test(ChatIndex::class)->call('startConversationWith', $this->userA->id);

    expect(Conversation::count())->toBe(1);
});

it('sends a message, updates the conversation, and broadcasts it', function () {
    Event::fake([MessageSent::class]);

    $conversation = Conversation::between($this->userA, $this->userB);

    Livewire::actingAs($this->userA)
        ->test(ChatIndex::class, ['recipient' => $this->userB])
        ->set('messageBody', 'Hello there')
        ->call('sendMessage')
        ->assertHasNoErrors();

    $message = ChatMessage::sole();

    expect($message->body)->toBe('Hello there')
        ->and($message->sender_id)->toBe($this->userA->id)
        ->and($message->conversation_id)->toBe($conversation->id)
        ->and($conversation->fresh()->last_message_at)->not->toBeNull();

    Event::assertDispatched(MessageSent::class, fn ($event) => $event->message->id === $message->id);
});

it('rejects sending an empty message', function () {
    Conversation::between($this->userA, $this->userB);

    Livewire::actingAs($this->userA)
        ->test(ChatIndex::class, ['recipient' => $this->userB])
        ->set('messageBody', '')
        ->call('sendMessage')
        ->assertHasErrors(['messageBody' => 'required']);

    expect(ChatMessage::count())->toBe(0);
});

it('marks only the other participant\'s messages as read when opening a conversation', function () {
    $conversation = Conversation::between($this->userA, $this->userB);

    $fromB = ChatMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $this->userB->id,
        'read_at' => null,
    ]);
    $fromA = ChatMessage::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $this->userA->id,
        'read_at' => null,
    ]);

    Livewire::actingAs($this->userA)
        ->test(ChatIndex::class)
        ->call('openConversation', $conversation->id);

    expect($fromB->fresh()->read_at)->not->toBeNull()
        ->and($fromA->fresh()->read_at)->toBeNull();
});

it('prevents opening a conversation the user is not a participant of', function () {
    $conversation = Conversation::between($this->userA, $this->userB);

    Livewire::actingAs($this->userC)
        ->test(ChatIndex::class)
        ->call('openConversation', $conversation->id)
        ->assertSet('conversationId', null);
});

it('finds users by name or email while excluding the current user', function () {
    $this->actingAs($this->userA);

    Livewire::test(ChatIndex::class)
        ->set('userSearch', $this->userB->name)
        ->assertSee($this->userB->name)
        ->assertDontSee($this->userA->name);
});
