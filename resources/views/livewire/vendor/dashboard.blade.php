<div class="max-w-5xl space-y-6">

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('vendor.products') }}" wire:navigate
            class="block rounded-xl border border-zinc-200 bg-white p-5 shadow-sm hover:border-zinc-300 transition-colors">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600">
                    <flux:icon.cube class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-semibold text-zinc-900">{{ $productsCount }}</p>
                    <p class="text-sm text-zinc-500">Products</p>
                </div>
            </div>
        </a>

        <a href="{{ route('vendor.orders') }}" wire:navigate
            class="block rounded-xl border border-zinc-200 bg-white p-5 shadow-sm hover:border-zinc-300 transition-colors">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600">
                    <flux:icon.shopping-bag class="size-5" />
                </div>
                <div>
                    <p class="text-2xl font-semibold text-zinc-900">{{ $ordersCount }}</p>
                    <p class="text-sm text-zinc-500">Orders</p>
                </div>
            </div>
        </a>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
        <flux:heading size="sm" class="mb-3">Your Vendor{{ $vendors->count() === 1 ? '' : 's' }}</flux:heading>
        <div class="space-y-2">
            @foreach ($vendors as $vendor)
                <div class="flex items-center gap-3 rounded-lg border border-zinc-100 p-3">
                    @if ($vendor->logo)
                        <img src="{{ $vendor->logo }}" alt="{{ $vendor->name }}" class="h-9 w-9 rounded-md object-cover border border-zinc-200" />
                    @else
                        <div class="h-9 w-9 rounded-md bg-zinc-100 flex items-center justify-center text-zinc-300">
                            <flux:icon.briefcase class="size-4" />
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-zinc-900">{{ $vendor->name }}</p>
                        @if ($vendor->address)
                            <p class="text-xs text-zinc-500 truncate">{{ $vendor->address }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
