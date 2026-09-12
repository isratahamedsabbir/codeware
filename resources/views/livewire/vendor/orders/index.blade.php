<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by order number…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
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
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Your Items</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Your Total</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Placed</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-indigo-50/30 transition-colors">
                            <td class="px-4 py-2.5">
                                <span class="text-sm font-mono text-zinc-700">{{ $order->order_number }}</span>
                            </td>
                            <td class="px-4 py-2.5 text-sm text-zinc-700">{{ $order->customer_name }}</td>
                            <td class="px-4 py-2.5 text-sm text-zinc-500">{{ $order->items->sum('quantity') }}</td>
                            <td class="px-4 py-2.5 text-sm font-medium text-zinc-900">{{ $order->currency }} {{ number_format((float) $order->items->sum('line_total'), 2) }}</td>
                            <td class="px-4 py-2.5">
                                @include('livewire.vendor.orders.partials.status-badge', ['status' => $order->status])
                            </td>
                            <td class="px-4 py-2.5 text-xs text-zinc-500">{{ $order->created_at?->toDisplay() }}</td>
                            <td class="px-4 py-2.5 text-right">
                                <a href="{{ route('vendor.orders.show', $order->id) }}" wire:navigate
                                    class="text-xs font-medium text-indigo-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <flux:icon.shopping-bag class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                                <p class="text-sm text-zinc-600">No orders yet.</p>
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
