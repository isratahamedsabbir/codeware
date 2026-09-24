<div class="space-y-4">

    {{-- Filters --}}
    <div class="admin-card p-4 flex items-center gap-3 flex-wrap">
        <div class="inline-flex rounded-lg border border-zinc-200 p-0.5 text-sm">
            @foreach (['pending' => "To Deliver ({$pendingCount})", 'delivered' => 'Delivered', '' => 'All'] as $value => $label)
                <button type="button" wire:click="$set('filter', '{{ $value }}')"
                    class="px-3 py-1.5 rounded-md font-medium transition-colors cursor-pointer {{ $filter === $value ? 'bg-primary text-white' : 'text-zinc-600 hover:bg-zinc-50' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <div class="relative w-full sm:max-w-xs sm:ml-auto">
            <flux:icon.magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-500" />
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Order #, name or phone…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Orders --}}
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($orders as $order)
            <a href="{{ route('delivery.orders.show', $order->id) }}" wire:navigate
                class="admin-card p-4 block hover:ring-2 hover:ring-indigo-100 transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-mono text-sm font-semibold text-zinc-900">{{ $order->order_number }}</p>
                        <p class="text-xs text-zinc-500">{{ $order->created_at?->toDisplay() }}</p>
                    </div>
                    @include('livewire.vendor.orders.partials.status-badge', ['status' => $order->status])
                </div>

                <div class="mt-3 space-y-1 text-sm">
                    <p class="font-medium text-zinc-800">{{ $order->customer_name }}</p>
                    <p class="text-zinc-500">{{ $order->customer_phone }}</p>
                    <p class="text-zinc-500 line-clamp-2">{{ $order->shipping_address }}</p>
                </div>

                <div class="mt-3 pt-3 border-t border-zinc-100 flex items-center justify-between text-sm">
                    <span class="text-zinc-500">{{ \App\Support\PaymentMethods::label($order->payment_method) }} · {{ ucfirst($order->payment_status) }}</span>
                    <span class="font-semibold text-zinc-900">{{ $order->currency }} {{ number_format((float) $order->total, 2) }}</span>
                </div>
            </a>
        @empty
            <div class="admin-card px-6 py-16 text-center sm:col-span-2 xl:col-span-3">
                <flux:icon.truck class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                <p class="text-sm text-zinc-600">No orders here.</p>
            </div>
        @endforelse
    </div>

    <div>
        {{ $orders->links() }}
    </div>

</div>
