<div
    class="flex h-[78vh] min-h-130 gap-4"
    x-data="{
        authId: {{ auth()->id() }},
        activeId: @entangle('conversationId'),
        channel: null,
        init() {
            this.channel = window.Echo.private('App.Models.User.' + this.authId)
                .listen('.message.sent', (event) => this.handleIncoming(event));
        },
        handleIncoming(event) {
            if (String(event.conversation_id) === String(this.activeId)) {
                this.$wire.call('markConversationRead', event.conversation_id);
            } else {
                this.$wire.$refresh();

                if (window.toastr) {
                    toastr.info(event.body, event.sender_name);
                }
            }
        },
    }"
>
    {{-- Conversation list --}}
    <div class="w-[320px] shrink-0 bg-white rounded-lg border border-zinc-100 shadow-sm flex flex-col overflow-hidden">
        <div class="p-3 border-b border-zinc-100 relative">
            <flux:input
                wire:model.live.debounce.300ms="userSearch"
                placeholder="Start a chat — search by name or email"
                icon="magnifying-glass"
            />

            @if ($this->searchResults->isNotEmpty())
                <div class="absolute left-3 right-3 top-full mt-1 bg-white border border-zinc-200 rounded-lg shadow-lg z-20 max-h-72 overflow-y-auto">
                    @foreach ($this->searchResults as $user)
                        <button
                            type="button"
                            wire:click="startConversationWith({{ $user->id }})"
                            wire:key="search-result-{{ $user->id }}"
                            class="w-full flex items-center gap-2.5 px-3 py-2.5 hover:bg-zinc-50 text-start transition-colors"
                        >
                            <flux:avatar size="sm" :name="$user->name" :initials="$user->initials()" />
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-zinc-800 truncate">{{ $user->name }}</div>
                                <div class="text-xs text-zinc-500 truncate">{{ $user->email }}</div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex-1 overflow-y-auto">
            @forelse ($this->conversations as $conversation)
                @php $other = $conversation->otherUser(auth()->user()); @endphp
                <button
                    type="button"
                    wire:click="openConversation({{ $conversation->id }})"
                    wire:key="conversation-{{ $conversation->id }}"
                    class="w-full flex items-center gap-2.5 px-3 py-3 border-b border-zinc-50 hover:bg-zinc-50 text-start transition-colors {{ $conversation->id === $this->conversationId ? 'bg-indigo-50/60' : '' }}"
                >
                    <flux:avatar :name="$other->name" :initials="$other->initials()" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-sm font-medium text-zinc-800 truncate">{{ $other->name }}</div>
                            @if ($conversation->latestMessage)
                                <div class="text-[11px] text-zinc-400 shrink-0">{{ $conversation->latestMessage->created_at->diffForHumans(null, true) }}</div>
                            @endif
                        </div>
                        <div class="flex items-center justify-between gap-2 mt-0.5">
                            <div class="text-xs text-zinc-500 truncate">
                                {{ $conversation->latestMessage->body ?? __('No messages yet') }}
                            </div>
                            @if ($conversation->unread_count > 0)
                                <flux:badge size="sm" color="indigo">{{ $conversation->unread_count }}</flux:badge>
                            @endif
                        </div>
                    </div>
                </button>
            @empty
                <div class="px-4 py-10 text-center text-sm text-zinc-500">
                    {{ __('No conversations yet. Search above to start one.') }}
                </div>
            @endforelse
        </div>
    </div>

    {{-- Thread --}}
    <div class="flex-1 bg-white rounded-lg border border-zinc-100 shadow-sm flex flex-col overflow-hidden">
        @if ($this->activeConversation)
            @php $other = $this->activeConversation->otherUser(auth()->user()); @endphp
            <div class="px-4 py-3 border-b border-zinc-100 flex items-center gap-2.5 shrink-0">
                <flux:avatar size="sm" :name="$other->name" :initials="$other->initials()" />
                <div class="text-sm font-semibold text-zinc-800">{{ $other->name }}</div>
            </div>

            {{--
                flex-col-reverse (+ space-y-reverse) instead of scrolling-via-JS: the
                container naturally sits pinned to its start-of-reversed-axis, which is
                the newest message, with no scrollTop calculation needed — and it stays
                pinned as messages are added, since that's just how the reversed flex
                flow behaves. The message order is reversed here (newest first in the
                DOM) so the reversed flow renders them in normal reading order visually
                (oldest at top, newest at bottom).
            --}}
            <div class="flex-1 overflow-y-auto px-4 py-4 flex flex-col-reverse space-y-3 space-y-reverse">
                @forelse ($this->threadMessages->reverse() as $message)
                    @php $isMine = $message->sender_id === auth()->id(); @endphp
                    <div wire:key="message-{{ $message->id }}" class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[70%] rounded-2xl px-3.5 py-2 text-sm {{ $isMine ? 'bg-brand-green text-white rounded-br-sm' : 'bg-zinc-100 text-zinc-800 rounded-bl-sm' }}">
                            <div class="whitespace-pre-wrap wrap-break-word">{{ $message->body }}</div>
                            <div class="text-[10px] mt-1 {{ $isMine ? 'text-white/70' : 'text-zinc-400' }}">
                                {{ $message->created_at->format('g:i A') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="h-full flex items-center justify-center text-sm text-zinc-400">
                        {{ __('Say hello 👋') }}
                    </div>
                @endforelse
            </div>

            <form wire:submit="sendMessage" class="p-3 border-t border-zinc-100 flex items-end gap-2 shrink-0">
                <div class="flex-1">
                    <flux:textarea
                        wire:model="messageBody"
                        rows="1"
                        placeholder="{{ __('Write a message...') }}"
                        x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.sendMessage(); }"
                    />
                    <flux:error name="messageBody" />
                </div>
                <flux:button type="submit" variant="primary" icon="paper-airplane">
                    {{ __('Send') }}
                </flux:button>
            </form>
        @else
            <div class="flex-1 flex items-center justify-center text-sm text-zinc-400">
                {{ __('Select a conversation or search for someone to start chatting.') }}
            </div>
        @endif
    </div>
</div>
