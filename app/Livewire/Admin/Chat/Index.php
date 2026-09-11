<?php

namespace App\Livewire\Admin\Chat;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\User;
use App\Support\AdminActivity;
use App\Support\EnvFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Index extends Component
{
    #[Url]
    public ?int $conversationId = null;

    public string $userSearch = '';

    public string $messageBody = '';

    /**
     * The Reverb (WebSocket broadcasting) credentials this chat's real-time
     * updates depend on — edited from a modal on this page since a broken
     * value here is exactly what breaks live chat. Only the client-facing
     * REVERB_* keys, not REVERB_SERVER_*: those configure the reverb:start
     * process itself, not something this admin-facing form should touch.
     *
     * @var array<string, string>
     */
    public array $reverbSettings = [];

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

    /**
     * Only the last 50 messages — an active conversation can otherwise grow
     * without bound, which both bloats the payload and (paired with the flex
     * layout below) keeps stretching the thread panel taller with every reply
     * instead of scrolling within a fixed height.
     */
    #[Computed]
    public function threadMessages(): Collection
    {
        return $this->activeConversation
            ? $this->activeConversation->messages()->with('sender')->latest()->limit(50)->get()->sortBy('id')->values()
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

    public function closeConversation(): void
    {
        $this->conversationId = null;
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

    public function openReverbSettings(): void
    {
        abort_unless(Gate::allows('access-admin-system'), 403);

        $current = EnvFile::all();

        $this->reverbSettings = [
            'REVERB_APP_ID' => $current['REVERB_APP_ID'] ?? '',
            'REVERB_APP_KEY' => $current['REVERB_APP_KEY'] ?? '',
            'REVERB_APP_SECRET' => $current['REVERB_APP_SECRET'] ?? '',
            'REVERB_HOST' => $current['REVERB_HOST'] ?? '',
            'REVERB_PORT' => $current['REVERB_PORT'] ?? '',
            'REVERB_SCHEME' => $current['REVERB_SCHEME'] ?? 'https',
        ];

        $this->dispatch('open-modal', name: 'reverb-settings');
    }

    public function saveReverbSettings(): void
    {
        abort_unless(Gate::allows('access-admin-system'), 403);

        $this->validate([
            'reverbSettings.REVERB_APP_ID' => 'required|string|max:255',
            'reverbSettings.REVERB_APP_KEY' => 'required|string|max:255',
            'reverbSettings.REVERB_APP_SECRET' => 'required|string|max:255',
            'reverbSettings.REVERB_HOST' => 'required|string|max:255',
            'reverbSettings.REVERB_PORT' => 'required|integer|min:1|max:65535',
            'reverbSettings.REVERB_SCHEME' => 'required|in:http,https',
        ]);

        try {
            // VITE_REVERB_* already reference these via ${REVERB_...} interpolation
            // in .env, so writing just the REVERB_* keys is enough to update both.
            EnvFile::set($this->reverbSettings);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', message: 'Could not save Reverb settings: '.$e->getMessage());

            return;
        }

        Artisan::call('config:clear');

        AdminActivity::log('updated', 'Reverb broadcasting settings updated');

        $this->dispatch('close-modal', name: 'reverb-settings');
        $this->dispatch('notify', message: 'Reverb settings saved. Restart the Reverb server (reverb:start) and rebuild frontend assets (npm run build) for the change to fully take effect.');
    }

    public function render()
    {
        return view('livewire.admin.chat.index')
            ->layout('layouts.admin', ['title' => 'Chat', 'hidePageHeading' => true]);
    }
}
