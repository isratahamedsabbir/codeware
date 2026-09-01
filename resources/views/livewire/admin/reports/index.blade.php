<div class="space-y-5">

    {{-- Summary cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border border-zinc-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Total Orders</p>
            <p class="text-2xl font-bold text-zinc-900">{{ number_format($totalOrders) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-zinc-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Revenue (Paid)</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format((float) $totalRevenue, 2) }}</p>
        </div>
        <div class="bg-white rounded-lg border border-zinc-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-1">Pending Payments</p>
            <p class="text-2xl font-bold text-amber-600">{{ number_format((float) $pendingAmount, 2) }}</p>
            <p class="text-xs text-zinc-400 mt-0.5">{{ $pendingCount }} order(s)</p>
        </div>
        <div class="bg-white rounded-lg border border-zinc-100 shadow-sm p-5">
            <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-2">Revenue by Method</p>
            <div class="space-y-1">
                @forelse ($revenueByMethod as $method => $amount)
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-600">{{ \App\Support\PaymentMethods::label($method) }}</span>
                        <span class="font-medium text-zinc-800">{{ number_format((float) $amount, 2) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-zinc-400">No paid orders yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Orders by status --}}
    <div class="bg-white rounded-lg border border-zinc-100 shadow-sm p-5">
        <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">Orders by Status</p>
        <div class="flex gap-3 flex-wrap">
            @forelse (\App\Models\Order::STATUSES as $status)
                <div class="px-4 py-2 rounded-lg bg-zinc-50 border border-zinc-100">
                    <span class="text-xs text-zinc-500">{{ ucfirst($status) }}</span>
                    <span class="ml-2 text-sm font-semibold text-zinc-800">{{ $ordersByStatus[$status] ?? 0 }}</span>
                </div>
            @empty
            @endforelse
        </div>
    </div>

    {{-- Filters + table --}}
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-zinc-100 space-y-3">
            <div class="flex items-center justify-between gap-3 flex-wrap">
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
                </div>

                <a href="{{ route('admin.reports.export', $this->filters()) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-zinc-200 text-zinc-600 bg-white hover:bg-zinc-50 transition-colors shrink-0">
                    <flux:icon.arrow-down-tray class="size-4" />
                    Export CSV
                </a>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                <flux:input type="date" wire:model.live="fromDate" class="sm:w-[160px]" />
                <flux:input type="date" wire:model.live="toDate" class="sm:w-[160px]" />

                <flux:button variant="ghost" wire:click="resetFilters" class="sm:ml-auto">Reset</flux:button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <div class="border border-zinc-100 rounded-lg">
                <table class="w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-zinc-50">
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Order #</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Customer</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Method</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Payment</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2.5 text-right text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Placed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($orders as $order)
                            <tr class="hover:bg-indigo-50/30 transition-colors">
                                <td class="px-4 py-3 font-mono text-xs text-zinc-700">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" wire:navigate class="hover:underline">
                                        <x-truncate :text="$order->order_number" />
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-zinc-800"><x-truncate :text="$order->customer_name" /></td>
                                <td class="px-4 py-3 text-sm text-zinc-600">{{ \App\Support\PaymentMethods::label($order->payment_method) }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600">{{ ucfirst($order->payment_status) }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600">{{ ucfirst($order->status) }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-zinc-900 text-right">{{ number_format((float) $order->total, 2) }}</td>
                                <td class="px-4 py-3 text-xs text-zinc-500">{{ $order->created_at->toDisplay('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center text-sm text-zinc-500">No orders match these filters.</td>
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

</div>
