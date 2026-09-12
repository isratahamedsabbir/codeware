<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Filters --}}
    <div class="px-6 py-5 space-y-3">
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
            <x-date-range-picker :from="$fromDate" :to="$toDate" />

            <x-searchable-select
                model="productFilter"
                search="productSearch"
                :options="$this->productOptions()"
                :selected-value="$productFilter"
                :selected-label="$this->selectedProductLabel()"
                all-label="All Products"
                placeholder="Search products…"
            />

            <div class="flex items-center gap-1.5">
                <flux:input type="number" step="0.01" min="0" wire:model.live.debounce.500ms="priceMin" placeholder="Min price" class="w-28" />
                <span class="text-zinc-400">–</span>
                <flux:input type="number" step="0.01" min="0" wire:model.live.debounce.500ms="priceMax" placeholder="Max price" class="w-28" />
            </div>

            <flux:button size="sm" variant="ghost" icon="arrow-down-tray" href="{{ route('admin.orders.export', $this->filters()) }}" class="sm:ml-auto">
                Export Filtered
            </flux:button>
            <flux:button size="sm" variant="ghost" wire:click="resetFilters">Reset</flux:button>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Order #</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Customer</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Items</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Total</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Payment</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Placed</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($orders as $order)
                        <tr class="group/row hover:bg-indigo-50/30 transition-colors">
                            <td class="px-2 py-2 text-center">
                                <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $order->id"
                                    wire:click="{{ $viewingId === $order->id ? 'closeDetails' : 'viewDetails('.$order->id.')' }}" />
                            </td>
                            <td class="px-4 py-2 font-mono text-xs text-zinc-700"><x-truncate :text="$order->order_number" /></td>
                            <td class="px-4 py-2">
                                <div class="text-sm font-medium text-zinc-900"><x-truncate :text="$order->customer_name" /></div>
                                <div class="text-xs text-zinc-500"><x-truncate :text="$order->customer_email" /></div>
                            </td>
                            <td class="hidden lg:table-cell px-4 py-2 text-sm text-zinc-600">{{ $order->items_count }}</td>
                            <td class="px-4 py-2 text-sm font-medium text-zinc-900">{{ number_format((float) $order->total, 2) }} {{ $order->currency }}</td>
                            <td class="hidden lg:table-cell px-4 py-2">
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
                            <td class="px-4 py-2">
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
                            <td class="hidden lg:table-cell px-4 py-2 text-xs text-zinc-500">{{ $order->created_at->toDisplay() }}</td>
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-50/30 border-l border-zinc-100 px-4 py-2">
                                <div class="flex items-center justify-center gap-1.5">
                                    <x-admin-row-actions :actions="[
                                        ['href' => route('admin.orders.show', $order->id), 'icon' => 'eye', 'label' => 'View', 'color' => 'primary'],
                                    ]" />

                                    {{-- One trigger, Admin/Customer sub-choice — a nested menu the
                                         shared row-actions dropdown doesn't support (it's a flat
                                         list), so this uses Flux's own dropdown/menu instead of a
                                         hand-rolled `absolute` one: this cell sits inside a
                                         horizontally-scrolling, sticky-column table, where a plain
                                         `position: absolute` popover gets clipped/misplaced by the
                                         scroll container. Flux's dropdown positions itself past that. --}}
                                    <flux:dropdown position="bottom" align="end">
                                        <button type="button" aria-label="Resend email"
                                            class="inline-flex items-center justify-center w-7 h-7 rounded border border-cyan-500 text-cyan-500 hover:bg-cyan-500 hover:text-white transition-all duration-150 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
                                            </svg>
                                        </button>
                                        <flux:menu>
                                            <flux:menu.item wire:click="resendEmail({{ $order->id }}, 'admin')">Admin</flux:menu.item>
                                            <flux:menu.item wire:click="resendEmail({{ $order->id }}, 'customer')">Customer</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </div>
                            </td>
                        </tr>
                        @if ($viewingId === $order->id)
                            <x-admin-row-details colspan="9">
                                <x-admin-row-details.item label="Items">{{ $order->items_count }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Payment method">{{ \App\Support\PaymentMethods::label($order->payment_method) }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Payment status">{{ ucfirst($order->payment_status) }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Placed">{{ $order->created_at->toDisplay() }}</x-admin-row-details.item>
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
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
