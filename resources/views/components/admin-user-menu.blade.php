<flux:dropdown position="bottom" align="end">
    <flux:button variant="subtle" square class="!p-1.5">
        <div class="flex items-center">
            @if (auth()->user()->photo_url)
                <img src="{{ auth()->user()->photo_url }}" alt="{{ auth()->user()->name }}"
                    class="size-8 rounded-xl object-cover shrink-0 shadow-sm">
            @else
                <div
                    class="size-8 rounded-xl bg-gradient-to-br from-brand-green to-brand-blue flex items-center justify-center text-white text-[10px] font-bold shrink-0">
                    {{ auth()->user()->initials() }}
                </div>
            @endif
        </div>
    </flux:button>

    <flux:menu>
        <flux:menu.heading>Account</flux:menu.heading>
        <flux:menu.item :href="route('admin.profile')" icon="user-circle" wire:navigate>
            My Profile
        </flux:menu.item>
        <flux:menu.item
            x-data
            as="button"
            x-on:click="location.reload()"
            icon="arrow-path"
            class="w-full cursor-pointer"
        >
            Refresh
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
