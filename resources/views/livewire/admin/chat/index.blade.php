<div class="flex flex-col h-full">
    <div class="mb-3 shrink-0">
        @include('partials.admin-breadcrumbs')
    </div>

    <div
        class="flex-1 min-h-0 flex flex-col md:flex-row gap-4"
        x-data="{
        authId: {{ auth()->id() }},
        activeId: @entangle('conversationId'),
        channel: null,
        panelHeight: null,
        init() {
            // The admin layout is a normal scrolling page (no fixed-height app
            // shell), so flex-1/h-full never actually bound this panel — it just
            // kept growing taller with every message instead of scrolling within
            // a fixed height. Pin it to the remaining viewport height instead.
            //
            // This is stored as reactive Alpine state bound via :style below,
            // rather than set directly on $el.style — a plain imperative style
            // mutation gets wiped the next time Livewire morphs this element
            // (its freshly server-rendered HTML never had that attribute), so
            // the box would silently revert to unbounded on every re-render
            // (new message, markConversationRead, etc). Alpine's own bindings
            // are reapplied after each morph, so this survives it.
            //
            // Done before the Echo subscription below so a broken/unavailable
            // Reverb connection can never stop the layout fix from applying.
            this.syncHeight();
            window.addEventListener('resize', () => this.syncHeight());

            try {
                this.channel = window.Echo.private('App.Models.User.' + this.authId)
                    .listen('.message.sent', (event) => this.handleIncoming(event));
            } catch (e) {
                console.error('Chat: failed to subscribe to the private channel', e);
            }
        },
        syncHeight() {
            const top = this.$el.getBoundingClientRect().top;
            this.panelHeight = `calc(100dvh - ${top}px - 1rem)`;
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
    :style="panelHeight ? `height: ${panelHeight}; max-height: ${panelHeight}` : ''"
>
    {{-- Conversation list --}}
    <div class="w-full md:w-[320px] shrink-0 min-h-0 bg-white rounded-[5px] border border-zinc-100 shadow-sm flex-col overflow-hidden {{ $this->activeConversation ? 'hidden md:flex' : 'flex' }}">
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

        <div class="flex-1 min-h-0 overflow-y-auto chat-scroll">
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
    <div class="flex-1 min-h-0 bg-white rounded-[5px] border border-zinc-100 shadow-sm flex-col overflow-hidden {{ $this->activeConversation ? 'flex' : 'hidden md:flex' }}">
        @if ($this->activeConversation)
            @php $other = $this->activeConversation->otherUser(auth()->user()); @endphp
            <div class="px-4 py-3 border-b border-zinc-100 flex items-center gap-2.5 shrink-0">
                <button type="button" wire:click="closeConversation" class="md:hidden -ml-1 p-1 rounded text-zinc-500 hover:bg-zinc-100 shrink-0" aria-label="{{ __('Back to conversations') }}">
                    <flux:icon.chevron-left class="size-5" />
                </button>
                <flux:avatar size="sm" :name="$other->name" :initials="$other->initials()" />
                <div class="text-sm font-semibold text-zinc-800">{{ $other->name }}</div>
            </div>

            <div
                x-ref="messageList"
                x-init="
                    const scrollToBottom = () => { $refs.messageList.scrollTop = $refs.messageList.scrollHeight; };
                    scrollToBottom();
                    new MutationObserver(() => $nextTick(scrollToBottom)).observe($refs.messageList, { childList: true });
                "
                class="flex-1 min-h-0 overflow-y-auto chat-scroll px-4 py-4 flex flex-col space-y-3"
            >
                @forelse ($this->threadMessages as $message)
                    @php $isMine = $message->sender_id === auth()->id(); @endphp
                    <div wire:key="message-{{ $message->id }}" class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[70%] rounded-2xl px-3.5 py-2 text-sm {{ $isMine ? 'bg-secondary text-white rounded-br-sm' : 'bg-zinc-100 text-zinc-800 rounded-bl-sm' }}">
                            <div class="whitespace-pre-wrap wrap-break-word">{{ $message->body }}</div>
                            <div class="text-[10px] mt-1 {{ $isMine ? 'text-white/70' : 'text-zinc-400' }}">
                                {{ $message->created_at->toDisplay('g:i A') }}
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
                        rows="2"
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
</div>
