<?php

namespace App\Livewire\Admin\Chat;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url]
    public ?int $conversationId = null;

    public string $userSearch = '';

    public string $messageBody = '';

    public function mount(?User $recipient = null): void
    {
        if ($recipient && $recipient->id !== auth()->id()) {
            $this->conversationId = Conversation::between(auth()->user(), $recipient)->id;
        }

        if ($this->conversationId) {
            $this->markConversationRead($this->conversationId);
        }
    }

    #[Computed]
    public function conversations(): Collection
    {
        return Conversation::forUser(auth()->user())
            ->with(['userOne', 'userTwo', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_id', '!=', auth()->id())->whereNull('read_at')])
            ->orderByRaw('COALESCE(last_message_at, created_at) DESC')
            ->get();
    }

    #[Computed]
    public function activeConversation(): ?Conversation
    {
        if (! $this->conversationId) {
            return null;
        }

        $conversation = Conversation::find($this->conversationId);

        if (! $conversation || ! $conversation->isParticipant(auth()->user())) {
            return null;
        }

        return $conversation;
    }

    #[Computed]
    public function threadMessages(): Collection
    {
        return $this->activeConversation
            ? $this->activeConversation->messages()->with('sender')->orderBy('created_at')->get()
            : collect();
    }

    #[Computed]
    public function searchResults(): Collection
    {
        $term = trim($this->userSearch);

        if ($term === '') {
            return collect();
        }

        return User::query()
            ->where('id', '!=', auth()->id())
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(10)
            ->get();
    }

    public function openConversation(int $conversationId): void
    {
        $conversation = Conversation::findOrFail($conversationId);

        abort_unless($conversation->isParticipant(auth()->user()), 403);

        $this->conversationId = $conversationId;
        $this->userSearch = '';

        $this->markConversationRead($conversationId);
    }

    public function startConversationWith(int $userId): void
    {
        $recipient = User::findOrFail($userId);

        abort_if($recipient->id === auth()->id(), 403);

        $this->openConversation(Conversation::between(auth()->user(), $recipient)->id);
    }

    public function sendMessage(): void
    {
        $this->validate([
            'messageBody' => ['required', 'string', 'max:5000'],
        ]);

        $conversation = $this->activeConversation;

        abort_unless($conversation, 404);

        $message = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'body' => trim($this->messageBody),
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        $this->messageBody = '';

        broadcast(new MessageSent($message));
    }

    public function markConversationRead(int $conversationId): void
    {
        $conversation = Conversation::find($conversationId);

        if (! $conversation || ! $conversation->isParticipant(auth()->user())) {
            return;
        }

        $conversation->messages()
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function render()
    {
        return view('livewire.admin.chat.index')
            ->layout('layouts.admin', ['title' => 'Chat', 'hidePageHeading' => true]);
    }
}
