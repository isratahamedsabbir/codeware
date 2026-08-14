<div class="bg-white rounded-lg border border-zinc-100 shadow-sm overflow-hidden">

    {{-- Filters --}}
    <div class="flex gap-3 px-6 py-5 border-b border-zinc-100 flex-wrap items-end">
        <div class="relative flex-1 min-w-[200px]">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Search order #, name, or email…" icon="magnifying-glass" />
        </div>

        <flux:select wire:model.live="statusFilter" class="min-w-[150px]">
            <flux:select.option value="">All statuses</flux:select.option>
            @foreach (\App\Models\Order::STATUSES as $status)
                <flux:select.option value="{{ $status }}">{{ ucfirst($status) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="paymentStatusFilter" class="min-w-[150px]">
            <flux:select.option value="">All payment statuses</flux:select.option>
            @foreach (\App\Models\Order::PAYMENT_STATUSES as $status)
                <flux:select.option value="{{ $status }}">{{ ucfirst($status) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="paymentMethodFilter" class="min-w-[150px]">
            <flux:select.option value="">All payment methods</flux:select.option>
            @foreach (\App\Support\PaymentMethods::available() as $key => $label)
                <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input type="date" wire:model.live="fromDate" class="w-[150px]" />
        <flux:input type="date" wire:model.live="toDate" class="w-[150px]" />

        <flux:button variant="ghost" wire:click="resetFilters">Reset</flux:button>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto px-6 py-4">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-zinc-50 border-b border-zinc-100">
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Order #</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Customer</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Items</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Payment</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Placed</th>
                        <th class="px-4 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr class="border-b border-zinc-50 hover:bg-indigo-50/30 transition-colors">
                            <td class="px-4 py-3.5 font-mono text-xs text-zinc-700">{{ $order->order_number }}</td>
                            <td class="px-4 py-3.5">
                                <div class="text-sm font-medium text-zinc-900">{{ $order->customer_name }}</div>
                                <div class="text-xs text-zinc-500">{{ $order->customer_email }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-zinc-600">{{ $order->items_count }}</td>
                            <td class="px-4 py-3.5 text-sm font-medium text-zinc-900">{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</td>
                            <td class="px-4 py-3.5">
                                <div class="text-xs text-zinc-600">{{ \App\Support\PaymentMethods::label($order->payment_method) }}</div>
                                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-[10.5px] font-medium
                                    {{ match ($order->payment_status) {
                                        'paid' => 'bg-green-50 text-green-700',
                                        'failed' => 'bg-rose-50 text-rose-700',
                                        'refunded' => 'bg-zinc-100 text-zinc-600',
                                        default => 'bg-amber-50 text-amber-700',
                                    } }}">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-medium
                                    {{ match ($order->status) {
                                        'delivered' => 'bg-green-50 text-green-700',
                                        'cancelled' => 'bg-rose-50 text-rose-700',
                                        'shipped' => 'bg-indigo-50 text-indigo-700',
                                        'processing' => 'bg-cyan-50 text-cyan-700',
                                        default => 'bg-amber-50 text-amber-700',
                                    } }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-zinc-500">{{ $order->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3.5 text-right">
                                <a href="{{ route('admin.orders.show', $order->id) }}" wire:navigate
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 text-zinc-600 bg-white hover:bg-zinc-50 transition-colors">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <flux:icon.shopping-bag class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                                <p class="text-sm text-zinc-600">No orders found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="px-6 py-3 border-t border-zinc-100">
        {{ $orders->links() }}
    </div>

</div>
