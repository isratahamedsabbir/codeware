<div>

    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials.admin-breadcrumbs', ['routeName' => 'admin.voucher-purchases'])
        </div>
    </div>

<div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Header --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        <select wire:model.live="statusFilter"
            class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
            style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
            <option value="">All statuses</option>
            <option value="issued">Issued</option>
            <option value="redeemed">Redeemed</option>
            <option value="expired">Expired</option>
        </select>
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search code, name or email…"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <colgroup>
                    <col style="width:5%">
                    <col class="hidden lg:table-column" style="width:5%">
                    <col style="width:16%">
                    <col style="width:18%">
                    <col class="hidden lg:table-column" style="width:11%">
                    <col style="width:9%">
                    <col style="width:10%">
                    <col class="hidden lg:table-column" style="width:10%">
                    <col style="width:16%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="hidden lg:table-cell px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Code</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Customer</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Voucher</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Value</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Expires</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($purchases as $purchase)
                        <tr class="group/row hover:bg-indigo-50/30 transition-colors">
                            <td class="px-1 py-2 text-center">
                                <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $purchase->id"
                                    wire:click="{{ $viewingId === $purchase->id ? 'closeDetails' : 'viewDetails('.$purchase->id.')' }}" />
                            </td>

                            <td class="hidden lg:table-cell px-2 py-2 text-center text-xs text-zinc-500">
                                <x-copy-text :text="$purchase->id" class="text-xs text-zinc-500">{{ $purchase->id }}</x-copy-text>
                            </td>

                            <td class="px-4 py-2">
                                <span class="font-mono text-sm font-semibold text-zinc-900"><x-truncate :text="$purchase->code" /></span>
                                <div class="text-[11px] text-zinc-400">{{ $purchase->purchased_at?->toDisplay() }}</div>
                            </td>

                            <td class="px-4 py-2">
                                <div class="text-sm text-zinc-800"><x-truncate :text="$purchase->customer_name" /></div>
                                <div class="text-[11px] text-zinc-400"><x-truncate :text="$purchase->customer_email" /></div>
                            </td>

                            <td class="hidden lg:table-cell px-4 py-2 text-sm text-zinc-600">
                                <x-truncate :text="$purchase->voucherName()" />
                            </td>

                            <td class="px-4 py-2 text-sm text-zinc-700">
                                {{ $purchase->currency }} {{ number_format((float) $purchase->value, 2) }}
                            </td>

                            <td class="px-4 py-2">
                                @php
                                    $statusStyles = [
                                        'issued' => 'bg-green-50 text-green-600 border-green-200',
                                        'redeemed' => 'bg-indigo-50 text-indigo-600 border-indigo-200',
                                        'expired' => 'bg-red-50 text-red-600 border-red-200',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium border {{ $statusStyles[$purchase->status] ?? 'bg-zinc-50 text-zinc-600 border-zinc-200' }}">
                                    {{ ucfirst($purchase->status) }}
                                </span>
                            </td>

                            <td class="hidden lg:table-cell px-4 py-2 text-xs text-zinc-500">
                                @if ($purchase->expires_at)
                                    <span class="{{ $purchase->isExpired() ? 'text-rose-500' : '' }}">
                                        {{ $purchase->expires_at->toDisplay() }}
                                    </span>
                                @else
                                    Never
                                @endif
                            </td>

                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['href' => \Illuminate\Support\Facades\URL::signedRoute('vouchers.public.show', ['voucher' => $purchase->code]), 'icon' => 'eye', 'label' => 'View', 'color' => 'primary'],
                                    ['href' => route('admin.voucher-purchases.download', $purchase), 'icon' => 'arrow-down-tray', 'label' => 'PDF', 'color' => 'zinc-500'],
                                    ['wireClick' => 'resendEmail(' . $purchase->id . ')', 'icon' => 'envelope', 'label' => 'Resend', 'color' => 'zinc-500'],
                                ]" />
                            </td>
                        </tr>
                        @if ($viewingId === $purchase->id)
                            <x-admin-row-details colspan="9">
                                <x-admin-row-details.item label="Recipient">{{ $purchase->recipient_name ?: '—' }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Phone">{{ $purchase->customer_phone ?: '—' }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Paid">{{ $purchase->currency }} {{ number_format((float) $purchase->price_paid, 2) }}</x-admin-row-details.item>
                                <x-admin-row-details.item label="Message">{{ $purchase->message ?: '—' }}</x-admin-row-details.item>
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-16 text-center">
                                <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 12v10H4V12M2 7h20v5H2zM12 22V7M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z" />
                                </svg>
                                <p class="text-sm text-zinc-600">No vouchers sold yet.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="px-6 py-3">
        {{ $purchases->links() }}
    </div>

</div>

</div>
