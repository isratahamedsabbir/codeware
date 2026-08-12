<flux:dropdown position="bottom" align="end">
    <flux:button variant="subtle" square class="!p-1.5">
        <div class="flex items-center gap-2.5">
            @if (auth()->user()->photo_url)
                <img src="{{ auth()->user()->photo_url }}" alt="{{ auth()->user()->name }}"
                    class="size-8 rounded-xl object-cover shrink-0 shadow-sm">
            @else
                <div
                    class="size-8 rounded-xl bg-gradient-to-br from-brand-green to-brand-blue flex items-center justify-center text-white text-[10px] font-bold shrink-0">
                    {{ auth()->user()->initials() }}
                </div>
            @endif
            <div class="hidden lg:block text-start">
                <p class="text-sm font-semibold text-zinc-800 leading-tight">{{ auth()->user()->name }}</p>
                <p class="text-xs text-zinc-500 leading-tight">{{ auth()->user()->email }}</p>
            </div>
            <flux:icon.chevrons-up-down class="hidden lg:block size-4 text-zinc-400" />
        </div>
    </flux:button>

    <flux:menu>
        <flux:menu.heading>Account</flux:menu.heading>
        <flux:menu.item :href="route('admin.profile')" icon="user-circle" wire:navigate>
            My Profile
        </flux:menu.item>
        <flux:menu.separator />
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <flux:menu.item
                as="button"
                type="submit"
                icon="arrow-right-start-on-rectangle"
                class="w-full cursor-pointer"
            >
                Log out
            </flux:menu.item>
        </form>
    </flux:menu>
</flux:dropdown>
