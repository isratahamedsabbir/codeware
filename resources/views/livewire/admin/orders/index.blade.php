<div class="bg-white rounded-lg shadow-sm overflow-hidden">

    {{-- Filters --}}
    <div class="px-6 py-5 border-b border-zinc-100 space-y-3">
        <div class="flex flex-col sm:flex-row gap-3">
            <x-per-page-select :options="$this->perPageOptions()" />

            <flux:select wire:model.live="statusFilter" class="sm:w-[170px]">
                <flux:select.option value="">All statuses</flux:select.option>
                @foreach (\App\Models\Order::STATUSES as $status)
                    <flux:select.option value="{{ $status }}">{{ ucfirst($status) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="paymentStatusFilter" class="sm:w-[170px]">
                <flux:select.option value="">All payment statuses</flux:select.option>
                @foreach (\App\Models\Order::PAYMENT_STATUSES as $status)
                    <flux:select.option value="{{ $status }}">{{ ucfirst($status) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="paymentMethodFilter" class="sm:w-[170px]">
                <flux:select.option value="">All payment methods</flux:select.option>
                @foreach (\App\Support\PaymentMethods::available() as $key => $label)
                    <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="relative flex-1">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="Search order #, name, or email…" icon="magnifying-glass" />
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <flux:input type="date" wire:model.live="fromDate" class="sm:w-[160px]" />
            <flux:input type="date" wire:model.live="toDate" class="sm:w-[160px]" />

            <flux:button variant="ghost" icon="arrow-down-tray" href="{{ route('admin.orders.export', $this->filters()) }}" class="sm:ml-auto">
                Export Filtered
            </flux:button>
            <flux:button variant="ghost" wire:click="resetFilters">Reset</flux:button>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-zinc-50">
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
                <tbody class="divide-y divide-gray-200">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-indigo-50/30 transition-colors">
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
                            <td class="px-4 py-3.5 text-xs text-zinc-500">{{ $order->created_at->toDisplay('M d, Y') }}</td>
                            <td class="px-4 py-3.5">
                                <div class="flex items-center justify-end gap-1.5">
                                    <div class="relative group">
                                        <a href="{{ route('admin.orders.show', $order->id) }}" wire:navigate
                                            aria-label="View order"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded border transition-all duration-150 border-primary text-primary hover:bg-primary hover:text-white hover:-translate-y-px"
                                            style="box-shadow:none"
                                            onmouseover="this.style.boxShadow='0 3px 8px rgba(99,102,241,.35)'"
                                            onmouseout="this.style.boxShadow='none'">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                        </a>
                                        <span class="pointer-events-none absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 rounded text-[11px] font-medium bg-primary text-white whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity z-10">
                                            View
                                            <span class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-primary"></span>
                                        </span>
                                    </div>
                                </div>
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

    <div class="px-6 py-3">
        {{ $orders->links() }}
    </div>

</div>
