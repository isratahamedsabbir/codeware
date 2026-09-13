<div class="space-y-5">

    {{-- Greeting Hero --}}
    <div class="admin-dashboard-hero flex flex-col sm:flex-row sm:items-center rounded-lg! justify-between gap-4 px-4 py-3">
        <div>
            <h1 class="text-xl font-extrabold tracking-tight text-zinc-900 sm:text-xl mb-0.5">
                Welcome back, {{ auth()->user()->name }}
            </h1>
            <p class="text-sm text-zinc-500">{{ $vendors->count() === 1 ? $vendors->first()->name : $vendors->count().' vendors assigned' }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-500 font-medium">
            <span class="inline-flex items-center gap-1.5">
                <flux:icon.briefcase class="size-4 text-primary" />
                Vendor Portal
            </span>
            <span class="text-zinc-300 select-none">&bull;</span>
            <span class="inline-flex items-center gap-1.5">
                <flux:icon.calendar class="size-4 text-zinc-400" />
                {{ now()->toDisplay('l, d F Y') }}
            </span>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">

        <a href="{{ route('vendor.products') }}" wire:navigate
            class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-primary! shadow-sm! block">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-zinc-500">Products</p>
                    <p class="admin-stat-value mt-2 font-extrabold text-zinc-900 leading-6">{{ $productsCount }}</p>
                </div>
                <div class="admin-stat-icon bg-blue-100">
                    <flux:icon.cube class="size-6 text-blue-600" />
                </div>
            </div>
        </a>

        <a href="{{ route('vendor.products') }}" wire:navigate
            class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-secondary! shadow-sm! block">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-zinc-500">Active Products</p>
                    <p class="admin-stat-value mt-2 font-extrabold text-zinc-900 leading-6">{{ $activeProductsCount }}</p>
                </div>
                <div class="admin-stat-icon bg-emerald-100">
                    <flux:icon.check-circle class="size-6 text-emerald-600" />
                </div>
            </div>
        </a>

        <a href="{{ route('vendor.products') }}" wire:navigate
            class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-rose-500! shadow-sm! block">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-zinc-500">Out of Stock</p>
                    <p class="admin-stat-value mt-2 font-extrabold text-zinc-900 leading-6">{{ $outOfStockCount }}</p>
                </div>
                <div class="admin-stat-icon bg-rose-100">
                    <flux:icon.exclamation-triangle class="size-6 text-rose-600" />
                </div>
            </div>
        </a>

        <a href="{{ route('vendor.orders') }}" wire:navigate
            class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-indigo-500! shadow-sm! block">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-zinc-500">Orders</p>
                    <p class="admin-stat-value mt-2 font-extrabold text-zinc-900 leading-6">{{ $ordersCount }}</p>
                </div>
                <div class="admin-stat-icon bg-indigo-100">
                    <flux:icon.shopping-bag class="size-6 text-indigo-600" />
                </div>
            </div>
        </a>

    </div>

    {{-- Quick Actions --}}
    <div>
        <h2 class="text-xs font-bold text-zinc-800 uppercase tracking-widest mb-4">Quick Actions</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-5">

            <a href="{{ route('vendor.products.create') }}" wire:navigate.hover
               class="admin-card p-4 flex flex-col sm:flex-row items-center gap-4 hover:border-blue-200 transition-colors group no-underline text-center sm:text-left cursor-pointer shadow-sm!">
                <div class="size-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0 border border-blue-100">
                    <flux:icon.plus class="size-5 text-primary" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-800 truncate group-hover:text-primary transition-colors">New Product</p>
                    <p class="text-xs text-zinc-400 truncate mt-0.5">Add to your catalog</p>
                </div>
            </a>

            <a href="{{ route('vendor.orders') }}" wire:navigate.hover
               class="admin-card p-4 flex flex-col sm:flex-row items-center gap-4 hover:border-green-200 transition-colors group no-underline text-center sm:text-left cursor-pointer shadow-sm!">
                <div class="size-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0 border border-green-100">
                    <flux:icon.shopping-bag class="size-5 text-secondary" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-800 truncate group-hover:text-secondary transition-colors">View Orders</p>
                    <p class="text-xs text-zinc-400 truncate mt-0.5">Track & invoice</p>
                </div>
            </a>

            <a href="{{ route('vendor.chat') }}" wire:navigate.hover
               class="admin-card p-4 flex flex-col sm:flex-row items-center gap-4 hover:border-indigo-200 transition-colors group no-underline text-center sm:text-left cursor-pointer shadow-sm!">
                <div class="size-10 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0 border border-indigo-100">
                    <flux:icon.chat-bubble-left-right class="size-5 text-indigo-600" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-800 truncate group-hover:text-indigo-600 transition-colors">Message Support</p>
                    <p class="text-xs text-zinc-400 truncate mt-0.5">Chat with admin/staff</p>
                </div>
            </a>

            <a href="{{ route('vendor.profile') }}" wire:navigate.hover
               class="admin-card p-4 flex flex-col sm:flex-row items-center gap-4 hover:border-zinc-300 transition-colors group no-underline text-center sm:text-left cursor-pointer shadow-sm!">
                <div class="size-10 rounded-lg bg-zinc-50 flex items-center justify-center shrink-0 border border-zinc-200">
                    <flux:icon.user-circle class="size-5 text-zinc-600" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-zinc-800 truncate group-hover:text-zinc-900 transition-colors">My Profile</p>
                    <p class="text-xs text-zinc-400 truncate mt-0.5">Account settings</p>
                </div>
            </a>

        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Recent Orders Feed --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xs font-bold text-zinc-800 uppercase tracking-widest">Recent Orders</h2>
                <a href="{{ route('vendor.orders') }}" wire:navigate.hover
                   class="text-xs font-bold text-primary hover:text-blue-700 transition-colors flex items-center gap-1">
                    View All
                    <flux:icon.chevron-right class="size-3" />
                </a>
            </div>

            <div class="admin-card divide-y divide-zinc-100 shadow-sm! border-0!">
                @forelse ($recentOrders as $order)
                    <a href="{{ route('vendor.orders.show', $order->id) }}" wire:navigate
                        class="flex items-center justify-between px-5 py-4 gap-4 hover:bg-slate-50/50 transition-colors duration-200 no-underline">
                        <div class="min-w-0 flex items-center gap-3">
                            <div class="size-8.5 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0 border border-indigo-100/50">
                                <flux:icon.shopping-bag class="size-4.5 text-indigo-600" />
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-zinc-800 truncate font-mono">{{ $order->order_number }}</p>
                                <p class="text-xs text-zinc-400 truncate mt-0.5">{{ $order->customer_name }} &middot; {{ $order->created_at?->toDisplay() }}</p>
                            </div>
                        </div>
                        @include('livewire.vendor.orders.partials.status-badge', ['status' => $order->status])
                    </a>
                @empty
                    <div class="px-5 py-10 text-sm text-zinc-400 text-center flex flex-col items-center justify-center gap-2">
                        <flux:icon.shopping-bag class="size-8 text-zinc-200" />
                        <p class="font-medium">No orders yet.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Your Vendor(s) --}}
        <div class="space-y-4">
            <h2 class="text-xs font-bold text-zinc-800 uppercase tracking-widest">Your Vendor{{ $vendors->count() === 1 ? '' : 's' }}</h2>

            <div class="admin-card divide-y divide-zinc-100 shadow-sm! border-0!">
                @forelse ($vendors as $vendor)
                    <div class="flex items-center gap-3 px-5 py-4">
                        @if ($vendor->logo)
                            <img src="{{ $vendor->logo }}" alt="{{ $vendor->name }}" class="h-9 w-9 rounded-md object-cover border border-zinc-200" />
                        @else
                            <div class="h-9 w-9 rounded-md bg-zinc-100 flex items-center justify-center text-zinc-300 shrink-0">
                                <flux:icon.briefcase class="size-4" />
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-zinc-800 truncate">{{ $vendor->name }}</p>
                            @if ($vendor->address)
                                <p class="text-xs text-zinc-400 truncate mt-0.5">{{ $vendor->address }}</p>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-10 text-sm text-zinc-400 text-center flex flex-col items-center justify-center gap-2">
                        <flux:icon.briefcase class="size-8 text-zinc-200" />
                        <p class="font-medium">No vendor assigned.</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
