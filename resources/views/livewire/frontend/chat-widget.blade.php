<div
    x-data="{
        storageKey: 'chat-widget-token',
        init() {
            const token = localStorage.getItem(this.storageKey);
            if (token) {
                $wire.resume(token);
            }
        }
    }"
    x-on:chat-widget-verified.window="localStorage.setItem('chat-widget-token', $event.detail.token)"
    class="fixed bottom-5 right-5 z-50"
>
    {{-- Bubble button --}}
    <button type="button" wire:click="toggle"
        class="flex items-center justify-center size-14 rounded-full bg-primary text-white shadow-lg transition hover:opacity-90"
        aria-label="{{ __('Open chat') }}">
        @if ($isOpen)
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
        @else
            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" />
            </svg>
        @endif
    </button>

    {{-- Panel --}}
    @if ($isOpen)
        <div class="absolute bottom-[calc(100%+0.75rem)] right-0 w-[22rem] max-w-[90vw] rounded-2xl bg-white shadow-2xl border border-zinc-100 overflow-hidden flex flex-col" style="max-height: 32rem;">

            <div class="bg-primary px-4 py-3 text-white shrink-0">
                <p class="font-semibold text-sm">{{ __('Chat with us') }}</p>
                @if ($step === 'chat' && $adminName)
                    <p class="text-xs text-white/80">{{ __('Chatting with :name', ['name' => $adminName]) }}</p>
                @else
                    <p class="text-xs text-white/80">{{ __("We're online — ask us anything.") }}</p>
                @endif
            </div>

            {{-- Step: name + email --}}
            @if ($step === 'form')
                <form wire:submit="requestOtp" class="p-4 space-y-3 overflow-y-auto">
                    <flux:field>
                        <flux:label>{{ __('Your Name') }}</flux:label>
                        <flux:input wire:model="name" placeholder="{{ __('Jane Doe') }}" autocomplete="name" />
                        <flux:error name="name" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Email') }}</flux:label>
                        <flux:input type="email" wire:model="email" placeholder="jane@example.com" autocomplete="email" />
                        <flux:error name="email" />
                    </flux:field>
                    <button type="submit" wire:loading.attr="disabled" wire:target="requestOtp"
                        class="w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                        <span wire:loading.remove wire:target="requestOtp">{{ __('Send verification code') }}</span>
                        <span wire:loading wire:target="requestOtp">{{ __('Sending...') }}</span>
                    </button>
                </form>
            @endif

            {{-- Step: OTP --}}
            @if ($step === 'otp')
                <form wire:submit="verifyOtp" class="p-4 space-y-3 overflow-y-auto">
                    <p class="text-xs text-zinc-500">
                        {{ __('We sent a 6-digit code to :email.', ['email' => $email]) }}
                    </p>
                    <flux:field>
                        <flux:label>{{ __('Verification Code') }}</flux:label>
                        <flux:input wire:model="otp" inputmode="numeric" maxlength="6" placeholder="123456" class="tracking-widest text-center font-mono" />
                        <flux:error name="otp" />
                    </flux:field>
                    <button type="submit" wire:loading.attr="disabled" wire:target="verifyOtp"
                        class="w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60">
                        <span wire:loading.remove wire:target="verifyOtp">{{ __('Verify & Start Chat') }}</span>
                        <span wire:loading wire:target="verifyOtp">{{ __('Verifying...') }}</span>
                    </button>
                    <div class="flex justify-between text-xs">
                        <button type="button" wire:click="changeEmail" class="text-zinc-400 hover:text-zinc-600">
                            {{ __('Change email') }}
                        </button>
                        <button type="button" wire:click="resendOtp" class="text-primary hover:underline">
                            {{ __('Resend code') }}
                        </button>
                    </div>
                </form>
            @endif

            {{-- Step: chat --}}
            @if ($step === 'chat')
                <div class="flex-1 overflow-y-auto px-4 py-3 space-y-2.5" wire:poll.3s>
                    @forelse ($this->messages as $message)
                        <div class="flex {{ $message->sender_id === $guestUserId ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[80%] rounded-2xl px-3.5 py-2 text-sm {{ $message->sender_id === $guestUserId ? 'bg-primary text-white rounded-br-sm' : 'bg-zinc-100 text-zinc-800 rounded-bl-sm' }}">
                                <div class="whitespace-pre-wrap break-words">{{ $message->body }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-zinc-400 text-center py-6">{{ __("Say hello — we'll reply shortly.") }}</p>
                    @endforelse
                </div>
                <form wire:submit="sendMessage" class="p-3 border-t border-zinc-100 flex items-end gap-2 shrink-0">
                    <flux:textarea wire:model="messageBody" rows="1" placeholder="{{ __('Type a message...') }}" class="flex-1" />
                    <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage"
                        class="shrink-0 rounded-lg bg-primary px-3.5 py-2.5 text-white hover:opacity-90 disabled:opacity-60">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="22" y1="2" x2="11" y2="13" />
                            <polygon points="22 2 15 22 11 13 2 9 22 2" />
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
