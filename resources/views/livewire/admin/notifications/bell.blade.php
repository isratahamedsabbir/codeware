<div wire:poll.30s>
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="subtle" square class="!p-1.5 relative" aria-label="Notifications">
            <span class="relative inline-flex">
                <flux:icon.bell class="size-5 text-zinc-500" />
                @if ($unreadCount > 0)
                    <span
                        class="absolute -top-1 -right-1.5 min-w-4 h-4 px-0.5 rounded-full bg-[#7cc242] text-white text-[9px] font-bold flex items-center justify-center">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </span>
        </flux:button>

        <flux:menu class="w-80 max-h-[520px] overflow-y-auto">
            <div class="flex items-center justify-between px-3 pt-3 pb-2">
                <p class="text-xs font-semibold text-zinc-800">Notifications</p>
                @if ($unreadCount > 0)
                    <button type="button" wire:click="markAllRead"
                        class="text-[11px] font-medium text-[#7cc242] hover:text-[#5d9c1f] transition-colors">
                        Mark all read
                    </button>
                @endif
            </div>

            <flux:menu.separator />

            @forelse ($notifications as $notification)
                <a href="{{ $notification->link ?? '#' }}" wire:navigate wire:click="markAsRead({{ $notification->id }})"
                    class="flex items-start gap-2.5 px-3 py-2.5 rounded-lg transition-colors
                    {{ $notification->read_at
                        ? 'hover:bg-zinc-100'
                        : 'bg-[#7cc242]/5 hover:bg-[#7cc242]/10' }}">
                    <span class="mt-1.5 shrink-0">
                        @if ($notification->read_at)
                            <span class="block size-1.5 rounded-full bg-zinc-300"></span>
                        @else
                            <span class="block size-1.5 rounded-full bg-[#7cc242]"></span>
                        @endif
                    </span>
                    <span class="min-w-0 flex-1">
                        <span
                            class="block text-[13px] leading-snug {{ $notification->read_at ? 'font-medium text-zinc-500' : 'font-semibold text-zinc-800' }}">
                            {{ $notification->title }}
                        </span>
                        @if ($notification->message)
                            <span class="block mt-0.5 text-xs text-zinc-500 truncate">{{ $notification->message }}</span>
                        @endif
                        <span class="block mt-0.5 text-[10px] text-zinc-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                </a>
            @empty
                <div class="px-4 py-10 text-center">
                    <flux:icon.bell-slash class="mx-auto size-6 text-zinc-300" />
                    <p class="mt-2 text-sm text-zinc-400">No notifications</p>
                </div>
            @endforelse
        </flux:menu>
    </flux:dropdown>
</div>
