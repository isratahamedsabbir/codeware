<div wire:poll.30s>
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="subtle" square class="!p-1.5 relative" aria-label="Notifications">
            <span class="relative inline-flex">
                <flux:icon.bell class="size-5 text-zinc-500" />
                @if ($unreadCount > 0)
                    <span
                        class="absolute -top-1 -right-1.5 min-w-4 h-4 px-0.5 rounded-full bg-secondary text-white text-[9px] font-bold flex items-center justify-center">
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
                        class="text-[11px] font-medium text-secondary hover:text-secondary-hover transition-colors">
                        Mark all read
                    </button>
                @endif
            </div>

            <flux:menu.separator />

            @forelse ($notifications as $notification)
                <div wire:key="notification-{{ $notification->id }}"
                    class="group relative flex items-start gap-2.5 px-3 py-2.5 mb-1.5 last:mb-0 rounded-lg transition-colors
                    {{ $notification->read_at
                        ? 'hover:bg-zinc-100'
                        : 'bg-secondary/5 hover:bg-secondary/10' }}">
                    <a href="{{ $notification->data['link'] ?? '#' }}" wire:navigate wire:click="markAsRead('{{ $notification->id }}')"
                        class="flex items-start gap-2.5 min-w-0 flex-1 pr-5">
                        <span class="mt-1.5 shrink-0">
                            @if ($notification->read_at)
                                <span class="block size-1.5 rounded-full bg-zinc-300"></span>
                            @else
                                <span class="block size-1.5 rounded-full bg-secondary"></span>
                            @endif
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="block text-[13px] leading-snug {{ $notification->read_at ? 'font-medium text-zinc-500' : 'font-semibold text-zinc-800' }}">
                                {{ $notification->data['title'] }}
                            </span>
                            @if ($notification->data['message'] ?? null)
                                <span class="block mt-0.5 text-xs text-zinc-500 truncate">{{ $notification->data['message'] }}</span>
                            @endif
                            <span class="block mt-0.5 text-[10px] text-zinc-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </a>
                    <button type="button" wire:click.stop.prevent="delete('{{ $notification->id }}')"
                        aria-label="Delete notification"
                        class="absolute top-2 right-2 flex items-center justify-center size-4 rounded-full text-zinc-400 opacity-0 group-hover:opacity-100 hover:bg-zinc-200 hover:text-zinc-700 transition-all cursor-pointer">
                        <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18" />
                            <line x1="6" y1="6" x2="18" y2="18" />
                        </svg>
                    </button>
                </div>
            @empty
                <div class="px-4 py-10 text-center">
                    <flux:icon.bell-slash class="mx-auto size-6 text-zinc-300" />
                    <p class="mt-2 text-sm text-zinc-400">No notifications</p>
                </div>
            @endforelse
        </flux:menu>
    </flux:dropdown>
</div>
