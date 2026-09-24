<div class="space-y-5">

    {{-- Greeting Hero — same as the vendor portal's dashboard. --}}
    <div class="admin-dashboard-hero flex flex-col sm:flex-row sm:items-center rounded-lg! justify-between gap-4 px-4 py-3">
        <div>
            <h1 class="text-xl font-extrabold tracking-tight text-zinc-900 sm:text-xl mb-0.5">
                Welcome back, {{ auth()->user()->name }}
            </h1>
            <p class="text-sm text-zinc-500">{{ $toDeliverCount }} {{ \Illuminate\Support\Str::plural('order', $toDeliverCount) }} waiting to be delivered</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-500 font-medium">
            <span class="inline-flex items-center gap-1.5">
                <flux:icon.truck class="size-4 text-primary" />
                Delivery Portal
            </span>
            <span class="text-zinc-300 select-none">&bull;</span>
            <span class="inline-flex items-center gap-1.5">
                <flux:icon.calendar class="size-4 text-zinc-400" />
                {{ now()->toDisplay('l, d F Y') }}
            </span>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">

        <a href="{{ route('delivery.orders', ['filter' => 'pending']) }}" wire:navigate
            class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-primary! shadow-sm! block">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-zinc-500">To Deliver</p>
                    <p class="admin-stat-value mt-2 font-extrabold text-zinc-900 leading-6">{{ $toDeliverCount }}</p>
                </div>
                <div class="admin-stat-icon bg-blue-100">
                    <flux:icon.truck class="size-6 text-blue-600" />
                </div>
            </div>
        </a>

        <a href="{{ route('delivery.orders', ['filter' => 'delivered']) }}" wire:navigate
            class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-secondary! shadow-sm! block">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-zinc-500">Delivered Today</p>
                    <p class="admin-stat-value mt-2 font-extrabold text-zinc-900 leading-6">{{ $deliveredTodayCount }}</p>
                </div>
                <div class="admin-stat-icon bg-emerald-100">
                    <flux:icon.check-circle class="size-6 text-emerald-600" />
                </div>
            </div>
        </a>

        <a href="{{ route('delivery.orders', ['filter' => 'delivered']) }}" wire:navigate
            class="admin-card admin-stat-card admin-showcase-stat relative border-0! border-t-4! border-indigo-500! shadow-sm! block">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-semibold text-zinc-500">Total Delivered</p>
                    <p class="admin-stat-value mt-2 font-extrabold text-zinc-900 leading-6">{{ $deliveredCount }}</p>
                </div>
                <div class="admin-stat-icon bg-indigo-100">
                    <flux:icon.archive-box class="size-6 text-indigo-600" />
                </div>
            </div>
        </a>

    </div>

    {{-- Next deliveries — oldest first, so the longest-waiting order is on top. --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-bold text-zinc-800 uppercase tracking-widest">Next Deliveries</h2>
            <a href="{{ route('delivery.orders') }}" wire:navigate.hover
               class="text-xs font-bold text-primary hover:text-blue-700 transition-colors flex items-center gap-1">
                View All
                <flux:icon.chevron-right class="size-3" />
            </a>
        </div>

        <div class="admin-card divide-y divide-zinc-100 shadow-sm! border-0!">
            @forelse ($nextOrders as $order)
                <a href="{{ route('delivery.orders.show', $order->id) }}" wire:navigate
                    class="flex items-center justify-between px-5 py-4 gap-4 hover:bg-slate-50/50 transition-colors duration-200 no-underline">
                    <div class="min-w-0 flex items-center gap-3">
                        <div class="size-8.5 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0 border border-indigo-100/50">
                            <flux:icon.map-pin class="size-4.5 text-indigo-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-zinc-800 truncate">{{ $order->customer_name }} <span class="font-mono text-xs text-zinc-400">{{ $order->order_number }}</span></p>
                            <p class="text-xs text-zinc-400 truncate mt-0.5">{{ $order->shipping_address }}</p>
                        </div>
                    </div>
                    @include('livewire.vendor.orders.partials.status-badge', ['status' => $order->status])
                </a>
            @empty
                <div class="px-5 py-10 text-sm text-zinc-400 text-center flex flex-col items-center justify-center gap-2">
                    <flux:icon.truck class="size-8 text-zinc-200" />
                    <p class="font-medium">Nothing to deliver right now.</p>
                </div>
            @endforelse
        </div>
    </div>

</div>
